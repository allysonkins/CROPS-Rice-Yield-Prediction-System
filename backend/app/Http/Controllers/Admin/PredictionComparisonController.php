<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\Prediction;
use App\Models\RiceVariety;
use Illuminate\Http\Request;

class PredictionComparisonController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $years = FarmRecord::query()
            ->when($user->role === 'farmer', function ($q) use ($user) {
                $q->whereIn('farm_id', Farm::where('user_id', $user->id)->pluck('id'));
            })
            ->select('year')->distinct()->orderBy('year', 'desc')
            ->pluck('year')->filter()->values();

        $varieties = RiceVariety::orderBy('name')->get(['id', 'name']);

        $barangays = Farm::query()
            ->when($user->role === 'farmer', fn ($q) => $q->where('user_id', $user->id))
            ->select('barangay')->distinct()->orderBy('barangay')
            ->pluck('barangay')->filter()->values();

        return view('admin.predictions.compare', compact('years', 'varieties', 'barangays'));
    }

    public function data(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'years'    => 'required|array|min:2|max:10',
            'years.*'  => 'integer|min:2000|max:2100',
            'farmer'   => 'nullable|string|max:255',
            'farm'     => 'nullable|string|max:255',
            'variety'  => 'nullable|integer|exists:rice_varieties,id',
            'season'   => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
        ]);

        $years    = array_map('intval', $request->input('years'));
        $farmer   = trim((string) $request->input('farmer', ''));
        $farm     = trim((string) $request->input('farm', ''));
        $variety  = $request->input('variety');
        $season   = $request->input('season');
        $barangay = $request->input('barangay');

        $records = FarmRecord::query()
            ->with(['farm.user', 'riceVariety'])
            ->whereIn('year', $years)
            ->when($user->role === 'farmer', function ($q) use ($user) {
                $q->whereIn('farm_id', Farm::where('user_id', $user->id)->pluck('id'));
            })
            ->when($farmer !== '', function ($q) use ($farmer) {
                $q->whereHas('farm.user', function ($q2) use ($farmer) {
                    $q2->where('name', 'like', "%{$farmer}%")
                       ->orWhere('rsbsa_number', 'like', "%{$farmer}%");
                });
            })
            ->when($farm !== '', function ($q) use ($farm) {
                $q->whereHas('farm', function ($q2) use ($farm) {
                    $q2->where('name', 'like', "%{$farm}%")
                       ->orWhere('id', $farm);
                });
            })
            ->when($variety, fn ($q) => $q->where('rice_variety_id', $variety))
            ->when($season, function ($q) use ($season) {
                $q->where(function ($q2) use ($season) {
                    $q2->where('season', 'like', "%{$season}%")
                       ->orWhere('season', $season . ' Season');
                });
            })
            ->when($barangay, fn ($q) => $q->whereHas('farm', fn ($q2) => $q2->where('barangay', $barangay)))
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success'      => true,
                'labels'       => [],
                'predicted'    => [],
                'actual'       => [],
                'meta'         => [],
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
        $missingYears = [];

        foreach ($years as $year) {
            $yearRecords = $grouped->get($year);

            if (!$yearRecords || $yearRecords->isEmpty()) {
                $missingYears[] = $year;
                $labels[]       = (string) $year;
                $predicted[]    = null;
                $actual[]       = null;
                $meta[]         = null;
                continue;
            }

            $yearPred = $yearRecords
                ->map(fn ($r) => $predictions->get($r->id)?->predicted_yield_tons_ha)
                ->filter(fn ($v) => $v !== null);

            $yearActual = $yearRecords
                ->where('status', 'Harvested')
                ->pluck('actual_yield_tons_ha')
                ->filter(fn ($v) => $v !== null);

            $labels[]    = (string) $year;
            $predicted[] = $yearPred->isNotEmpty() ? round($yearPred->avg(), 2) : null;
            $actual[]    = $yearActual->isNotEmpty() ? round($yearActual->avg(), 2) : null;

            $meta[] = [
                'records'   => $yearRecords->count(),
                'predicted' => $yearPred->count(),
                'actual'    => $yearActual->count(),
                'farmer'    => $yearRecords->first()->farm->user->name ?? null,
                'variety'   => $yearRecords->first()->riceVariety->name ?? null,
            ];
        }

        return response()->json([
            'success'      => true,
            'labels'       => $labels,
            'predicted'    => $predicted,
            'actual'       => $actual,
            'meta'         => $meta,
            'missingYears' => $missingYears,
        ]);
    }
}