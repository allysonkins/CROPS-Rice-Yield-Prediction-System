<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\RiceVariety;
use App\Services\PredictionService;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class FarmRecordController extends Controller
{
    public function index(Request $request)
{
    $user = auth()->user();

    $baseQuery = FarmRecord::with([
        'farm',
        'riceVariety',
        'predictions' => fn($q) => $q->where('model_type', 'XGBoost')->orderByDesc('created_at'),
    ]);

    if ($user->role === 'farmer') {
        $farmIds = Farm::where('user_id', $user->id)->pluck('id');
        $baseQuery->whereIn('farm_id', $farmIds);
    }

    // ── Sorting ─────────────────────────────────────────────
    $sort = $request->input('sort', 'created_at');
    $direction = $request->input('direction', 'desc');

    $allowedSorts = ['season', 'year', 'created_at', 'farm_id', 'rice_variety_id', 'fertilizer_kg_ha', 'status'];
    if (!in_array($sort, $allowedSorts)) $sort = 'created_at';
    if (!in_array($direction, ['asc', 'desc'])) $direction = 'desc';

    // ── Year filter ─────────────────────────────────────────
    $currentYear = (int) now()->year;
    $yearFilter  = $request->input('year', (string) $currentYear);

    $availableYears = (clone $baseQuery)
        ->select('year')
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year')
        ->filter()
        ->map(fn($y) => (int) $y);

    if (!$availableYears->contains($currentYear)) {
        $availableYears->prepend($currentYear);
        $availableYears = $availableYears->sortDesc()->values();
    }

    if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
        $baseQuery->where('year', (int) $yearFilter);
    }

    $baseQuery->orderBy($sort, $direction);
    $farmRecords = $baseQuery->get();

    $sortParams = ['sort' => $sort, 'direction' => $direction];

    $farms = $user->role === 'farmer'
        ? Farm::where('user_id', $user->id)->get()
        : Farm::orderBy('name')->get();

    $varieties = RiceVariety::orderBy('name')->get();

    return view('admin.farm-records.index', compact(
        'farmRecords',
        'farms',
        'varieties',
        'sortParams',
        'availableYears',
        'yearFilter',
        'currentYear'
    ));
}

    public function create()
    {
        if (auth()->user()->role === 'farmer') abort(403);

        $farms = Farm::orderBy('name')->get();
        $varieties = RiceVariety::orderBy('name')->get();

        return view('admin.farm-records.create', compact('farms', 'varieties'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role === 'farmer') {
            return response()->json(['success' => false, 'error' => 'Farmers cannot add farm records.'], 403);
        }

        try {
            $validated = $request->validate([
                'farm_id' => 'required|exists:farms,id',
                'rice_variety_id' => 'required|exists:rice_varieties,id',
                'season' => 'required|string|max:255',
                'year' => 'required|integer|min:2000|max:' . (now()->year + 5),
                'fertilizer_kg_ha' => 'required|numeric|min:0',
                'historical_yield_tons_ha' => 'nullable|numeric|min:0',
                'actual_yield_tons_ha' => 'nullable|numeric|min:0',
                'seeding_method' => 'nullable|string|max:255',
                'status' => 'required|in:Vegetative,Harvested',
            ]);

            if ($validated['status'] === 'Vegetative') {
                $validated['actual_yield_tons_ha'] = null;
            }

            // ── Planting-time weather snapshot ──
            // Feeds the ML retraining pipeline. Failures are non-fatal.
            try {
                $weather = app(WeatherService::class)->getWeather();
                $validated['weather_temperature'] = $weather['temperature'] ?? null;
                $validated['weather_rainfall']    = $weather['rainfall'] ?? null;
                $validated['weather_humidity']    = $weather['humidity'] ?? null;
                $validated['weather_captured_at'] = now();
            } catch (\Exception $e) {
                \Log::warning('Weather capture failed on farm record creation', ['error' => $e->getMessage()]);
            }

            $record = FarmRecord::create($validated);

            // Auto-generate prediction for Vegetative records
            if ($record->status === 'Vegetative') {
                try {
                    app(PredictionService::class)->predict($record);
                } catch (\Exception $e) {
                    \Log::warning('Auto-prediction failed on farm record create', ['error' => $e->getMessage()]);
                }
            }

            log_activity('created', 'Farm record created', $record, [
                'farm' => $record->farm->name,
                'variety' => $record->riceVariety->name,
                'season' => $record->season,
                'year' => $record->year,
                'status' => $record->status,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Farm record created' . ($record->status === 'Vegetative' ? ' and prediction generated!' : '!'),
                    'data' => $record
                ], 201);
            }

            return redirect()->route('admin.farm-records.index')->with('success', 'Farm record created successfully!');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'errors' => $e->errors(), 'error' => 'Please correct the highlighted fields.'], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (QueryException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Database error occurred.')->withInput();

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Failed to save record: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save record.')->withInput();
        }
    }

    public function edit($id)
    {
        if (auth()->user()->role === 'farmer') abort(403);

        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);
        $farms = Farm::orderBy('name')->get();
        $varieties = RiceVariety::orderBy('name')->get();

        return view('admin.farm-records.edit', compact('farmRecord', 'farms', 'varieties'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->role === 'farmer') {
            return response()->json(['success' => false, 'error' => 'Farmers cannot edit farm records.'], 403);
        }

        try {
            $farmRecord = FarmRecord::findOrFail($id);

            $validated = $request->validate([
                'farm_id' => 'required|exists:farms,id',
                'rice_variety_id' => 'required|exists:rice_varieties,id',
                'season' => 'required|string|max:255',
                'year' => 'required|integer|min:2000|max:' . (now()->year + 5),
                'fertilizer_kg_ha' => 'required|numeric|min:0',
                'historical_yield_tons_ha' => 'nullable|numeric|min:0',
                'seeding_method' => 'nullable|string|max:255',
            ]);

            // Refresh planting-time weather snapshot if stale (>7 days)
            if ($farmRecord->weather_captured_at === null || $farmRecord->weather_captured_at->diffInDays(now()) >= 7) {
                try {
                    $weather = app(WeatherService::class)->getWeather();
                    $validated['weather_temperature'] = $weather['temperature'] ?? null;
                    $validated['weather_rainfall']    = $weather['rainfall'] ?? null;
                    $validated['weather_humidity']    = $weather['humidity'] ?? null;
                    $validated['weather_captured_at'] = now();
                } catch (\Exception $e) {
                    \Log::warning('Weather refresh failed', ['error' => $e->getMessage()]);
                }
            }

            $oldData = $farmRecord->only(['season', 'year', 'fertilizer_kg_ha', 'rice_variety_id', 'seeding_method']);
            $farmRecord->update($validated);

            log_activity('updated', 'Farm record updated', $farmRecord, [
                'old' => $oldData,
                'new' => $farmRecord->only(['season', 'year', 'fertilizer_kg_ha', 'rice_variety_id', 'seeding_method']),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Farm record updated!',
                    'data' => $farmRecord
                ]);
            }

            return redirect()->route('admin.farm-records.index')->with('success', 'Farm record updated successfully!');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'errors' => $e->errors(), 'error' => 'Please correct the highlighted fields.'], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (QueryException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Database error occurred.')->withInput();

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Failed to update record: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to update record.')->withInput();
        }
    }

    /**
     * Quick action: mark a Vegetative record as Harvested with the actual yield.
     *
     * Harvest marks the end of the season, so we also capture a *second*
     * weather snapshot (harvest-day) alongside the planting-time one. The
     * ML retraining pipeline can then learn from both.
     */
    public function markHarvested(Request $request, $id)
    {
        if (auth()->user()->role === 'farmer') {
            return response()->json(['success' => false, 'error' => 'Farmers cannot update farm records.'], 403);
        }

        try {
            $validated = $request->validate([
                'actual_yield_tons_ha' => 'required|numeric|min:0|max:20',
            ]);

            $farmRecord = FarmRecord::findOrFail($id);

            if ($farmRecord->status === 'Harvested') {
                return response()->json(['success' => false, 'error' => 'This record is already marked as Harvested.'], 400);
            }

            $updateData = [
                'status'               => 'Harvested',
                'actual_yield_tons_ha' => $validated['actual_yield_tons_ha'],
            ];

            // ── Harvest-day weather snapshot ──
            // A second snapshot at the end of the season. Combined with the
            // planting-time snapshot, the model can learn signals like
            // "yield after a dry harvest month". Non-fatal on failure.
            try {
                $weather = app(WeatherService::class)->getWeather();
                $updateData['harvest_weather_temperature'] = $weather['temperature'] ?? null;
                $updateData['harvest_weather_rainfall']    = $weather['rainfall'] ?? null;
                $updateData['harvest_weather_humidity']    = $weather['humidity'] ?? null;
                $updateData['harvest_weather_captured_at'] = now();
            } catch (\Exception $e) {
                \Log::warning('Harvest weather capture failed', ['error' => $e->getMessage()]);
            }

            // Backfill planting-time weather if it was missing (legacy records
            // created before the weather columns existed).
            if ($farmRecord->weather_captured_at === null) {
                try {
                    $weather = app(WeatherService::class)->getWeather();
                    $updateData['weather_temperature'] = $weather['temperature'] ?? null;
                    $updateData['weather_rainfall']    = $weather['rainfall'] ?? null;
                    $updateData['weather_humidity']    = $weather['humidity'] ?? null;
                    $updateData['weather_captured_at'] = now();
                } catch (\Exception $e) {
                    \Log::warning('Planting weather backfill failed on markHarvested', ['error' => $e->getMessage()]);
                }
            }

            $farmRecord->update($updateData);

            // Keep the prediction so we can compare Predicted vs Actual later.
            // This is the feedback loop that tells farmers (and the CAO) how
            // accurate the model is.

            log_activity('updated', 'Farm record marked as harvested', $farmRecord, [
                'farm'          => $farmRecord->farm->name ?? 'N/A',
                'variety'       => $farmRecord->riceVariety->name ?? 'N/A',
                'year'          => $farmRecord->year,
                'actual_yield'  => $validated['actual_yield_tons_ha'],
                'harvest_weather_captured' => $farmRecord->harvest_weather_captured_at !== null,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Record marked as harvested!',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
                'error'   => 'Please enter a valid actual yield.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        if (auth()->user()->role === 'farmer') {
            return back()->with('error', 'Farmers cannot delete farm records.');
        }

        $farmRecord = FarmRecord::with(['farm', 'riceVariety', 'predictions'])->findOrFail($id);

        // Capture info for the activity log BEFORE deletion
        $farmName    = $farmRecord->farm->name ?? 'N/A';
        $varietyName = $farmRecord->riceVariety->name ?? 'N/A';
        $season      = $farmRecord->season ?? 'N/A';
        $year        = $farmRecord->year ?? 'N/A';
        $predCount   = $farmRecord->predictions->count();

        DB::beginTransaction();
        try {
            // Delete all associated predictions first
            $farmRecord->predictions()->delete();

            // Then delete the farm record itself
            $farmRecord->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.farm-records.index')
                ->with('error', 'Failed to delete farm record: ' . $e->getMessage());
        }

        log_activity('deleted', 'Farm record deleted', null, [
            'farm'                => $farmName,
            'variety'             => $varietyName,
            'season'              => $season,
            'year'                => $year,
            'predictions_removed' => $predCount,
        ]);

        $message = "Farm record deleted successfully!";
        if ($predCount > 0) {
            $message .= " ({$predCount} prediction" . ($predCount !== 1 ? 's' : '') . " removed.)";
        }

        return redirect()->route('admin.farm-records.index')->with('success', $message);
    }

    public function show($id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);
        return view('admin.farm-records.partials.detail', compact('farmRecord'));
    }
}