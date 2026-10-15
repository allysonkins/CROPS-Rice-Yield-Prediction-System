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
    private const SEASONS         = ['Dry Season', 'Wet Season', 'Off-Season'];
    private const SEEDING_METHODS = ['Transplanted', 'Direct-Seeded'];
    private const WATER_SOURCES   = ['Rainfed', 'Irrigated', 'Both'];

    public function index(Request $request)
    {
        $user = auth()->user();

        $baseQuery = FarmRecord::query()
            ->with([
                'farm.user',
                'riceVariety',
                'predictions' => fn ($q) => $q->where('model_type', 'XGBoost')->orderByDesc('created_at'),
            ]);

        if ($user->role === 'farmer') {
            $farmIds = Farm::where('user_id', $user->id)->pluck('id');
            $baseQuery->whereIn('farm_id', $farmIds);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $allowedSorts = ['season', 'year', 'created_at', 'farm_id', 'rice_variety_id', 'fertilizer_kg_ha', 'status'];
        if (!in_array($sort, $allowedSorts)) $sort = 'created_at';
        if (!in_array($direction, ['asc', 'desc'])) $direction = 'desc';

        $currentYear = (int) now()->year;
        $yearFilter  = $request->input('year', (string) $currentYear);

        $availableYears = (clone $baseQuery)
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->map(fn ($y) => (int) $y);

        if (!$availableYears->contains($currentYear)) {
            $availableYears->prepend($currentYear);
            $availableYears = $availableYears->sortDesc()->values();
        }

        if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
            $baseQuery->where('year', (int) $yearFilter);
        }

        $statusPriority = "CASE WHEN farm_records.status = 'Vegetative' THEN 0 ELSE 1 END";

        $baseQuery
            ->orderByRaw("{$statusPriority} ASC")
            ->orderBy($sort, $direction)
            ->orderByDesc('created_at');

        $farmRecords = $baseQuery->get();

        $sortParams = ['sort' => $sort, 'direction' => $direction];

        $farms = $user->role === 'farmer'
            ? Farm::where('user_id', $user->id)->orderBy('name')->get()
            : Farm::orderBy('name')->get();

        $varieties = RiceVariety::orderBy('name')->get(['id', 'name']);

        $barangays = Farm::query()
            ->when($user->role === 'farmer', fn ($q) => $q->where('user_id', $user->id))
            ->select('barangay')->distinct()->orderBy('barangay')
            ->pluck('barangay')->filter();

        return view('admin.farm-records.index', compact(
            'farmRecords',
            'farms',
            'varieties',
            'barangays',
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
                'farm_id'                  => 'required|exists:farms,id',
                'rice_variety_id'          => 'required|exists:rice_varieties,id',
                'season'                   => 'required|string|in:' . implode(',', self::SEASONS),
                'year'                     => 'required|integer|min:2000|max:' . (now()->year + 5),
                'fertilizer_kg_ha'         => 'required|numeric|min:0|max:250',
                'historical_yield_tons_ha' => 'nullable|numeric|min:0|max:20',
                'seeding_method'           => 'nullable|string|in:' . implode(',', self::SEEDING_METHODS),
                'water_source'             => 'nullable|in:' . implode(',', self::WATER_SOURCES),
                'status'                   => 'required|in:Vegetative',
            ]);

            // ── Rule: one record per (farm, season, year) ──
            $duplicate = $this->findDuplicateFarmRecord(
                (int) $validated['farm_id'],
                $validated['season'],
                (int) $validated['year']
            );

            if ($duplicate) {
                $existingVariety = $duplicate->riceVariety->name ?? 'another variety';
                $msg = "This farm already has a {$validated['season']} record for {$validated['year']} "
                     . "(currently planted with {$existingVariety}). "
                     . "Only one record per season is allowed. Edit the existing record instead.";

                if ($request->ajax()) {
                    return response()->json(['success' => false, 'error' => $msg], 422);
                }
                return back()->withErrors(['season' => $msg])->withInput();
            }

            $validated['status']               = 'Vegetative';
            $validated['actual_yield_tons_ha'] = null;

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

            try {
                app(PredictionService::class)->predict($record);
            } catch (\Exception $e) {
                \Log::warning('Auto-prediction failed on farm record create', ['error' => $e->getMessage()]);
            }

            log_activity('created', 'Farm record created', $record, [
                'farm'    => $record->farm->name ?? 'N/A',
                'variety' => $record->riceVariety->name ?? 'N/A',
                'season'  => $record->season,
                'year'    => $record->year,
                'status'  => $record->status,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Farm record created and prediction generated!',
                    'data'    => $record,
                ], 201);
            }

            return redirect()->route('admin.farm-records.index')
                ->with('success', 'Farm record created successfully!');

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

            if ($request->filled('status') || $request->filled('actual_yield_tons_ha')) {
                $msg = 'Status and actual yield are locked. Use the Harvest button to record the harvest.';

                if ($request->ajax()) {
                    return response()->json(['success' => false, 'error' => $msg], 422);
                }
                return back()->withErrors(['status' => $msg])->withInput();
            }

            $validated = $request->validate([
                'farm_id'                  => 'required|exists:farms,id',
                'rice_variety_id'          => 'required|exists:rice_varieties,id',
                'season'                   => 'required|string|in:' . implode(',', self::SEASONS),
                'year'                     => 'required|integer|min:2000|max:' . (now()->year + 5),
                'fertilizer_kg_ha'         => 'required|numeric|min:0|max:250',
                'historical_yield_tons_ha' => 'nullable|numeric|min:0|max:20',
                'seeding_method'           => 'nullable|string|in:' . implode(',', self::SEEDING_METHODS),
                'water_source'             => 'nullable|in:' . implode(',', self::WATER_SOURCES),
            ]);

            // ── Rule: one record per (farm, season, year), excluding self ──
            $duplicate = $this->findDuplicateFarmRecord(
                (int) $validated['farm_id'],
                $validated['season'],
                (int) $validated['year'],
                $farmRecord->id
            );

            if ($duplicate) {
                $existingVariety = $duplicate->riceVariety->name ?? 'another variety';
                $msg = "This farm already has a {$validated['season']} record for {$validated['year']} "
                     . "(currently planted with {$existingVariety}). "
                     . "Only one record per season is allowed.";

                if ($request->ajax()) {
                    return response()->json(['success' => false, 'error' => $msg], 422);
                }
                return back()->withErrors(['season' => $msg])->withInput();
            }

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
                    'message' => 'Farm record updated!',
                    'data'    => $farmRecord,
                ]);
            }

            return redirect()->route('admin.farm-records.index')
                ->with('success', 'Farm record updated successfully!');

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

            try {
                $weather = app(WeatherService::class)->getWeather();
                $updateData['harvest_weather_temperature'] = $weather['temperature'] ?? null;
                $updateData['harvest_weather_rainfall']    = $weather['rainfall'] ?? null;
                $updateData['harvest_weather_humidity']    = $weather['humidity'] ?? null;
                $updateData['harvest_weather_captured_at'] = now();
            } catch (\Exception $e) {
                \Log::warning('Harvest weather capture failed', ['error' => $e->getMessage()]);
            }

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

            log_activity('updated', 'Farm record marked as harvested', $farmRecord, [
                'farm'                     => $farmRecord->farm->name ?? 'N/A',
                'variety'                  => $farmRecord->riceVariety->name ?? 'N/A',
                'year'                     => $farmRecord->year,
                'actual_yield'             => $validated['actual_yield_tons_ha'],
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

        $farmName    = $farmRecord->farm->name ?? 'N/A';
        $varietyName = $farmRecord->riceVariety->name ?? 'N/A';
        $season      = $farmRecord->season ?? 'N/A';
        $year        = $farmRecord->year ?? 'N/A';
        $predCount   = $farmRecord->predictions->count();

        DB::beginTransaction();
        try {
            $farmRecord->predictions()->delete();
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

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Return an existing farm record with the same farm, season, and year
     * — or null if none exists. `rice_variety_id` is deliberately NOT part
     * of the check: a farm can only have one record per season per year,
     * regardless of which variety it's planted with.
     *
     * Pass $excludeId on update to skip the record being edited.
     */
    private function findDuplicateFarmRecord(
        int $farmId,
        string $season,
        int $year,
        ?int $excludeId = null
    ): ?FarmRecord {
        return FarmRecord::query()
            ->with('riceVariety')
            ->where('farm_id', $farmId)
            ->whereIn('season', $this->normalizeSeason($season))
            ->where('year', $year)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->first();
    }

    /**
     * Return every spelling variant of a season so historical data
     * like "Dry" and "Dry Season" collide correctly.
     */
    private function normalizeSeason(string $season): array
    {
        $s = strtolower(trim($season));

        if (str_contains($s, 'off')) return ['Off-Season', 'Off Season', 'OffSeason'];
        if (str_contains($s, 'dry')) return ['Dry Season', 'Dry'];
        if (str_contains($s, 'wet')) return ['Wet Season', 'Wet'];

        return [$season];
    }
}