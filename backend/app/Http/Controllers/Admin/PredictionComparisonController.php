<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\Prediction;
use App\Models\RiceVariety;
use App\Models\User;
use Illuminate\Http\Request;

class PredictionComparisonController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Dropdown fallbacks
    |--------------------------------------------------------------------------
    */
    private const SEEDING_METHOD_FALLBACK = ['Transplanted', 'Direct Seeded', 'Direct-Seeded'];
    private const WATER_SOURCE_FALLBACK   = ['Rainfed', 'Irrigated', 'Both'];
    private const SOIL_TYPE_FALLBACK      = ['Clay Loam', 'Silty Clay', 'Sandy Loam', 'Clay', 'Loam'];

    public function index()
    {
        $user = auth()->user();

        $farmIdsForUser = fn () => Farm::where('user_id', $user->id)->pluck('id');

        // ── Years ──
        $years = FarmRecord::query()
            ->when($user->role === 'farmer', fn ($q) => $q->whereIn('farm_id', $farmIdsForUser()))
            ->select('year')->distinct()->orderBy('year', 'desc')
            ->pluck('year')->filter()->values();

        // ── Varieties (only ones actually used in records) ──
        $varieties = RiceVariety::query()
            ->whereIn('id', FarmRecord::query()
                ->when($user->role === 'farmer', fn ($q) => $q->whereIn('farm_id', $farmIdsForUser()))
                ->select('rice_variety_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        // ── Barangays ──
        $barangays = Farm::query()
            ->when($user->role === 'farmer', fn ($q) => $q->where('user_id', $user->id))
            ->select('barangay')->distinct()->orderBy('barangay')
            ->pluck('barangay')->filter()->values();

        // ── Seeding methods ──
        $seedingMethods = FarmRecord::query()
            ->when($user->role === 'farmer', fn ($q) => $q->whereIn('farm_id', $farmIdsForUser()))
            ->select('seeding_method')->distinct()
            ->pluck('seeding_method')
            ->filter()
            ->merge(self::SEEDING_METHOD_FALLBACK)
            ->unique()
            ->sort()
            ->values();

        // ── Water sources ──
        $waterSources = FarmRecord::query()
            ->when($user->role === 'farmer', fn ($q) => $q->whereIn('farm_id', $farmIdsForUser()))
            ->select('water_source')->distinct()
            ->pluck('water_source')
            ->filter()
            ->merge(self::WATER_SOURCE_FALLBACK)
            ->unique()
            ->sort()
            ->values();

        // ── Soil types ──
        $soilTypes = Farm::query()
            ->when($user->role === 'farmer', fn ($q) => $q->where('user_id', $user->id))
            ->select('soil_type')->distinct()
            ->pluck('soil_type')
            ->filter()
            ->merge(self::SOIL_TYPE_FALLBACK)
            ->unique()
            ->sort()
            ->values();

        // ── Farms ──
        $farms = Farm::query()
            ->when($user->role === 'farmer', fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name', 'barangay']);

        // ── Farmers — only those who own at least one farm ──
        $farmers = User::query()
            ->where('role', 'farmer')
            ->when($user->role === 'farmer', fn ($q) => $q->where('id', $user->id))
            ->whereIn('id', Farm::query()->select('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'rsbsa_number']);

        return view('admin.predictions.compare', compact(
            'years',
            'varieties',
            'barangays',
            'seedingMethods',
            'waterSources',
            'soilTypes',
            'farms',
            'farmers'
        ));
    }

    public function data(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'years'          => 'required|array|min:2|max:10',
            'years.*'        => 'integer|min:2000|max:2100',
            'farmer_id'      => 'nullable|integer|exists:users,id',
            'variety'        => 'nullable|integer|exists:rice_varieties,id',
            'season'         => 'nullable|string|max:50',
            'barangay'       => 'nullable|string|max:255',
            'seeding_method' => 'nullable|string|max:100',
            'water_source'   => 'nullable|string|max:50',
            'soil_type'      => 'nullable|string|max:100',
            'farm_id'        => 'nullable|integer|exists:farms,id',
        ]);

        $years         = array_map('intval', $request->input('years'));
        $farmerId      = $request->input('farmer_id');
        $variety       = $request->input('variety');
        $season        = $request->input('season');
        $barangay      = $request->input('barangay');
        $seedingMethod = $request->input('seeding_method');
        $waterSource   = $request->input('water_source');
        $soilType      = $request->input('soil_type');
        $farmId        = $request->input('farm_id');

        $records = FarmRecord::query()
            ->with(['farm.user', 'riceVariety'])
            ->whereIn('year', $years)
            ->when($user->role === 'farmer', function ($q) use ($user) {
                $q->whereIn('farm_id', Farm::where('user_id', $user->id)->pluck('id'));
            })
            ->when($farmerId, function ($q) use ($farmerId) {
                $q->whereHas('farm', fn ($q2) => $q2->where('user_id', $farmerId));
            })
            ->when($variety,  fn ($q) => $q->where('rice_variety_id', $variety))
            ->when($season, function ($q) use ($season) {
                if ($season === 'Off-Season') {
                    $q->where(function ($q2) {
                        $q2->where('season', 'Off-Season')
                           ->orWhere('season', 'Off Season')
                           ->orWhere('season', 'OffSeason');
                    });
                } else {
                    $q->where('season', 'like', "%{$season}%");
                }
            })
            ->when($barangay,      fn ($q) => $q->whereHas('farm', fn ($q2) => $q2->where('barangay', $barangay)))
            ->when($seedingMethod, fn ($q) => $q->where('seeding_method', $seedingMethod))
            ->when($waterSource,   fn ($q) => $q->where('water_source', $waterSource))
            ->when($soilType,      fn ($q) => $q->whereHas('farm', fn ($q2) => $q2->where('soil_type', $soilType)))
            ->when($farmId,        fn ($q) => $q->where('farm_id', $farmId))
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success'      => true,
                'labels'       => [],
                'predicted'    => [],
                'actual'       => [],
                'meta'         => [],
                'perYear'      => [],
                'overall'      => null,
                'missingYears' => $years,
                'message'      => 'No farm records match the selected criteria.',
            ]);
        }

        $recordIds = $records->pluck('id');

        $predictions = Prediction::whereIn('farm_record_id', $recordIds)
            ->where('model_type', 'XGBoost')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('farm_record_id')
            ->keyBy('farm_record_id');

        $grouped = $records->groupBy('year');

        $labels       = [];
        $predicted    = [];
        $actual       = [];
        $meta         = [];
        $perYear      = [];
        $missingYears = [];

        $totalMatched = 0;
        $totalAbsErr  = 0.0;
        $totalPctErr  = 0.0;

        foreach ($years as $year) {
            $yearRecords = $grouped->get($year);

            if (!$yearRecords || $yearRecords->isEmpty()) {
                $missingYears[] = $year;
                $labels[]       = (string) $year;
                $predicted[]    = null;
                $actual[]       = null;
                $meta[]         = null;
                $perYear[]      = [
                    'year' => $year, 'records' => 0,
                    'predicted' => 0, 'actual' => 0, 'matched' => 0,
                    'avgPred' => null, 'avgAct' => null,
                    'delta' => null, 'mae' => null, 'mape' => null,
                ];
                continue;
            }

            $yearPred = $yearRecords
                ->map(fn ($r) => $predictions->get($r->id)?->predicted_yield_tons_ha)
                ->filter(fn ($v) => $v !== null);

            $yearActual = $yearRecords
                ->where('status', 'Harvested')
                ->pluck('actual_yield_tons_ha')
                ->filter(fn ($v) => $v !== null);

            $avgPred = $yearPred->isNotEmpty() ? round($yearPred->avg(), 2) : null;
            $avgAct  = $yearActual->isNotEmpty() ? round($yearActual->avg(), 2) : null;

            $matched = 0; $sumAbs = 0.0; $sumPct = 0.0;
            foreach ($yearRecords as $r) {
                $pred    = $predictions->get($r->id)?->predicted_yield_tons_ha;
                $actual_ = $r->actual_yield_tons_ha;

                if ($pred !== null && $actual_ !== null && (float) $actual_ > 0) {
                    $absErr  = abs($pred - $actual_);
                    $sumAbs += $absErr;
                    $sumPct += ($absErr / (float) $actual_) * 100;
                    $matched++;
                }
            }

            $mae  = $matched > 0 ? round($sumAbs / $matched, 2) : null;
            $mape = $matched > 0 ? round($sumPct / $matched, 1) : null;

            $totalMatched += $matched;
            $totalAbsErr  += $sumAbs;
            $totalPctErr  += $sumPct;

            $labels[]    = (string) $year;
            $predicted[] = $avgPred;
            $actual[]    = $avgAct;

            $delta = ($avgPred !== null && $avgAct !== null)
                ? round($avgAct - $avgPred, 2)
                : null;

            $meta[] = [
                'records'   => $yearRecords->count(),
                'predicted' => $yearPred->count(),
                'actual'    => $yearActual->count(),
                'matched'   => $matched,
                'farmer'    => $yearRecords->first()->farm->user->name ?? null,
                'variety'   => $yearRecords->first()->riceVariety->name ?? null,
            ];

            $perYear[] = [
                'year'      => $year,
                'records'   => $yearRecords->count(),
                'predicted' => $yearPred->count(),
                'actual'    => $yearActual->count(),
                'matched'   => $matched,
                'avgPred'   => $avgPred,
                'avgAct'    => $avgAct,
                'delta'     => $delta,
                'mae'       => $mae,
                'mape'      => $mape,
            ];
        }

        $overall = [
            'matched' => $totalMatched,
            'mae'     => $totalMatched > 0 ? round($totalAbsErr / $totalMatched, 2) : null,
            'mape'    => $totalMatched > 0 ? round($totalPctErr / $totalMatched, 1) : null,
            'years'   => count($years),
        ];

        return response()->json([
            'success'      => true,
            'labels'       => $labels,
            'predicted'    => $predicted,
            'actual'       => $actual,
            'meta'         => $meta,
            'perYear'      => $perYear,
            'overall'      => $overall,
            'missingYears' => $missingYears,
        ]);
    }
}