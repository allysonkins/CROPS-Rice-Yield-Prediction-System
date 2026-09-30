<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\RiceVariety;
use App\Services\PredictionService;
use App\Services\WeatherService;
use App\Support\RecommendationCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FarmRecordController extends Controller
{
    public function index()
    {
        $farmIds = Farm::where('user_id', auth()->id())->pluck('id');

        $farmRecords = FarmRecord::whereIn('farm_id', $farmIds)
            ->with(['farm', 'riceVariety', 'predictions'])
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $farms     = Farm::where('user_id', auth()->id())->orderBy('name')->get();
        $varieties = RiceVariety::orderBy('name')->get();

        return view('farmer.farm-records.index', compact('farmRecords', 'farms', 'varieties'));
    }

    public function create(Request $request)
    {
        if (!request()->ajax() && !request()->wantsJson()) {
            return redirect()->route('farmer.farm-records.index');
        }

        $farms         = Farm::where('user_id', auth()->id())->orderBy('name')->get();
        $varieties     = RiceVariety::orderBy('name')->get();
        $prefillFarmId = $request->input('farm_id');

        return view('farmer.farm-records.create', compact('farms', 'varieties', 'prefillFarmId'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'farm_id' => ['required', 'exists:farms,id', function ($attr, $val, $fail) {
                    $owns = Farm::where('id', $val)->where('user_id', auth()->id())->exists();
                    if (!$owns) $fail('You can only add seasons to your own farms.');
                }],
                'rice_variety_id'          => 'required|exists:rice_varieties,id',
                'season'                   => 'required|string|max:255',
                'year'                     => 'required|integer|min:2000|max:' . (now()->year + 5),
                'fertilizer_kg_ha'         => 'required|numeric|min:0',
                'historical_yield_tons_ha' => 'nullable|numeric|min:0',
                'seeding_method'           => 'nullable|string|max:255',
                'status'                   => 'required|in:Vegetative,Harvested',
                'actual_yield_tons_ha'     => 'nullable|numeric|min:0',
            ]);

            if ($validated['status'] === 'Vegetative') {
                $validated['actual_yield_tons_ha'] = null;
            }

            try {
                $weather = app(WeatherService::class)->getWeather();
                $validated['weather_temperature'] = $weather['temperature'] ?? null;
                $validated['weather_rainfall']    = $weather['rainfall'] ?? null;
                $validated['weather_humidity']    = $weather['humidity'] ?? null;
                $validated['weather_captured_at'] = now();
            } catch (\Exception $e) {
                \Log::warning('Weather capture failed (farmer store)', ['error' => $e->getMessage()]);
            }

            $record = FarmRecord::create($validated);

            if ($record->status === 'Vegetative') {
                try {
                    app(PredictionService::class)->predict($record);
                } catch (\Exception $e) {
                    \Log::warning('Auto-prediction failed (farmer store)', ['error' => $e->getMessage()]);
                }
            }

            // ── New season changes the recommendation inputs ──
            RecommendationCache::forget($record->farm_id);

            log_activity('created', 'Farm record created by farmer', $record, [
                'farm'    => $record->farm->name,
                'variety' => $record->riceVariety->name,
                'season'  => $record->season,
                'year'    => $record->year,
                'source'  => 'farmer-self-service',
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Season added' . ($record->status === 'Vegetative' ? ' and prediction generated!' : '!'),
                    'data'    => $record,
                ], 201);
            }

            return redirect()->route('farmer.farms.show', $record->farm_id)
                ->with('success', 'Season added successfully!');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'errors' => $e->errors(), 'error' => 'Please correct the highlighted fields.'], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save season.')->withInput();
        }
    }

    public function edit($id)
    {
        if (!request()->ajax() && !request()->wantsJson()) {
            return redirect()->route('farmer.farm-records.index');
        }

        $farmIds = Farm::where('user_id', auth()->id())->pluck('id');

        $farmRecord = FarmRecord::whereIn('farm_id', $farmIds)
            ->with(['farm', 'riceVariety'])
            ->findOrFail($id);

        $farms     = Farm::where('user_id', auth()->id())->orderBy('name')->get();
        $varieties = RiceVariety::orderBy('name')->get();

        return view('farmer.farm-records.edit', compact('farmRecord', 'farms', 'varieties'));
    }

    public function update(Request $request, $id)
    {
        try {
            $farmIds    = Farm::where('user_id', auth()->id())->pluck('id');
            $farmRecord = FarmRecord::whereIn('farm_id', $farmIds)->findOrFail($id);

            $validated = $request->validate([
                'rice_variety_id'          => 'required|exists:rice_varieties,id',
                'season'                   => 'required|string|max:255',
                'year'                     => 'required|integer|min:2000|max:' . (now()->year + 5),
                'fertilizer_kg_ha'         => 'required|numeric|min:0',
                'historical_yield_tons_ha' => 'nullable|numeric|min:0',
                'seeding_method'           => 'nullable|string|max:255',
            ]);

            if ($farmRecord->weather_captured_at === null || $farmRecord->weather_captured_at->diffInDays(now()) >= 7) {
                try {
                    $weather = app(WeatherService::class)->getWeather();
                    $validated['weather_temperature'] = $weather['temperature'] ?? null;
                    $validated['weather_rainfall']    = $weather['rainfall'] ?? null;
                    $validated['weather_humidity']    = $weather['humidity'] ?? null;
                    $validated['weather_captured_at'] = now();
                } catch (\Exception $e) {}
            }

            $oldData = $farmRecord->only(['season', 'year', 'fertilizer_kg_ha', 'rice_variety_id', 'seeding_method']);
            $farmRecord->update($validated);

            if ($farmRecord->status === 'Vegetative') {
                try {
                    app(PredictionService::class)->predict($farmRecord);
                } catch (\Exception $e) {}
            }

            // ── Record changed → recommendation inputs changed ──
            RecommendationCache::forget($farmRecord->farm_id);

            log_activity('updated', 'Farm record updated by farmer', $farmRecord, [
                'old' => $oldData,
                'new' => $farmRecord->only(['season', 'year', 'fertilizer_kg_ha', 'rice_variety_id', 'seeding_method']),
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Season updated!', 'data' => $farmRecord]);
            }

            return redirect()->route('farmer.farms.show', $farmRecord->farm_id)
                ->with('success', 'Season updated successfully!');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to update season.')->withInput();
        }
    }

    public function markHarvested(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'actual_yield_tons_ha' => 'required|numeric|min:0|max:20',
            ]);

            $farmIds    = Farm::where('user_id', auth()->id())->pluck('id');
            $farmRecord = FarmRecord::whereIn('farm_id', $farmIds)->findOrFail($id);

            if ($farmRecord->status === 'Harvested') {
                return response()->json(['success' => false, 'error' => 'This record is already marked as Harvested.'], 400);
            }

            $farmRecord->update([
                'status'               => 'Harvested',
                'actual_yield_tons_ha' => $validated['actual_yield_tons_ha'],
            ]);

            // ── Actual yield feeds future historical_yield features → recommendation changes ──
            RecommendationCache::forget($farmRecord->farm_id);

            log_activity('updated', 'Farmer marked record as harvested', $farmRecord, [
                'farm'         => $farmRecord->farm->name,
                'variety'      => $farmRecord->riceVariety->name,
                'actual_yield' => $validated['actual_yield_tons_ha'],
            ]);

            return response()->json(['success' => true, 'message' => 'Harvest recorded!']);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'error' => 'Please enter a valid yield.'], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $farmIds    = Farm::where('user_id', auth()->id())->pluck('id');
        $farmRecord = FarmRecord::whereIn('farm_id', $farmIds)
            ->with(['farm', 'riceVariety', 'predictions'])
            ->findOrFail($id);

        $farmName    = $farmRecord->farm->name ?? 'N/A';
        $varietyName = $farmRecord->riceVariety->name ?? 'N/A';
        $season      = $farmRecord->season ?? 'N/A';
        $predCount   = $farmRecord->predictions->count();
        $farmId      = $farmRecord->farm_id;

        DB::beginTransaction();
        try {
            $farmRecord->predictions()->delete();
            $farmRecord->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete season: ' . $e->getMessage());
        }

        // ── Removing a season changes what the model sees for this farm ──
        RecommendationCache::forget($farmId);

        log_activity('deleted', 'Farmer deleted farm record', null, [
            'farm'                => $farmName,
            'variety'             => $varietyName,
            'season'              => $season,
            'predictions_removed' => $predCount,
        ]);

        $message = 'Season record deleted.';
        if ($predCount > 0) {
            $message .= " ({$predCount} prediction" . ($predCount !== 1 ? 's' : '') . " removed.)";
        }

        return redirect()->route('farmer.farms.show', $farmId)->with('success', $message);
    }

    public function show($id)
    {
        $farmIds    = Farm::where('user_id', auth()->id())->pluck('id');
        $farmRecord = FarmRecord::whereIn('farm_id', $farmIds)
            ->with(['farm', 'riceVariety', 'predictions'])
            ->findOrFail($id);

        return view('farmer.farm-records.partials.detail', compact('farmRecord'));
    }
}