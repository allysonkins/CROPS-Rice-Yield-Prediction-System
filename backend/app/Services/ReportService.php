<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\Prediction;
use App\Models\RiceVariety;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Master payload — every report (PDF/Excel) uses this.
     */
    public function build(): array
    {
        return [
            'generated_at'         => now(),
            'overview'             => $this->overview(),
            'farmers'              => $this->farmers(),
            'farms'                => $this->farms(),
            'farm_records'         => $this->farmRecords(),
            'predictions'          => $this->predictions(),
            'by_barangay'          => $this->byBarangay(),
            'by_variety'           => $this->byVariety(),
            'accuracy'             => $this->predictionAccuracy(),
            'top_farms'            => $this->topFarms(),
            'low_farms'            => $this->lowFarms(),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // OVERVIEW
    // ═══════════════════════════════════════════════════════════

    public function overview(): array
    {
        $latest = $this->latestPredictions();

        $avgPredicted = $latest->avg('predicted_yield_tons_ha');
        $avgActual    = FarmRecord::where('status', 'Harvested')
            ->whereNotNull('actual_yield_tons_ha')
            ->avg('actual_yield_tons_ha');

        $totalArea = Farm::sum('land_area_ha');

        return [
            'total_farmers'         => User::where('role', 'farmer')->count(),
            'verified_farmers'      => User::where('role', 'farmer')->whereNotNull('verified_by_cao_at')->count(),
            'pending_farmers'       => User::where('role', 'farmer')->whereNull('verified_by_cao_at')->count(),
            'total_farms'           => Farm::count(),
            'total_area_ha'         => round($totalArea, 2),
            'total_varieties'       => RiceVariety::count(),
            'total_farm_records'    => FarmRecord::count(),
            'vegetative_records'    => FarmRecord::where('status', 'Vegetative')->count(),
            'harvested_records'     => FarmRecord::where('status', 'Harvested')->count(),
            'total_predictions'     => Prediction::count(),
            'avg_predicted_yield'   => $avgPredicted !== null ? round($avgPredicted, 2) : null,
            'avg_actual_yield'      => $avgActual !== null ? round($avgActual, 2) : null,
            'yield_class_counts'    => $latest->groupBy('predicted_class')->map->count()->toArray(),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // DETAIL SHEETS
    // ═══════════════════════════════════════════════════════════

    public function farmers(): Collection
    {
        return User::where('role', 'farmer')
            ->withCount('farms')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'Name'          => $u->name,
                'RSBSA Number'  => $u->rsbsa_number ?? '—',
                'Phone'         => $u->phone ?? '—',
                'Barangay'      => $u->barangay ?? '—',
                'Farms'         => $u->farms_count,
                'Verified'      => $u->verified_by_cao_at ? 'Yes' : 'Pending',
                'Registered'    => $u->created_at?->format('M d, Y') ?? '—',
            ]);
    }

    public function farms(): Collection
    {
        return Farm::with(['user', 'farmRecords'])
            ->withCount('farmRecords')
            ->orderBy('name')
            ->get()
            ->map(fn($f) => [
                'Farm Name'     => $f->name,
                'Barangay'      => $f->barangay,
                'Farmer'        => $f->user->name ?? 'Unassigned',
                'Area (ha)'     => number_format($f->land_area_ha, 2),
                'Soil Type'     => $f->soil_type ?? '—',
                'Records'       => $f->farm_records_count,
                'Latitude'      => $f->latitude ?? '—',
                'Longitude'     => $f->longitude ?? '—',
            ]);
    }

    public function farmRecords(): Collection
    {
        return FarmRecord::with(['farm', 'riceVariety', 'predictions'])
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($r) {
                $latest = $r->predictions->sortByDesc('created_at')->first();

                return [
                    'Farm'              => $r->farm->name ?? '—',
                    'Barangay'          => $r->farm->barangay ?? '—',
                    'Year'              => $r->year ?? '—',
                    'Season'            => $r->season ?? '—',
                    'Variety'           => $r->riceVariety->name ?? '—',
                    'Seeding Method'    => $r->seeding_method ?? '—',
                    'Fertilizer (kg/ha)' => number_format($r->fertilizer_kg_ha, 1),
                    'Historical Yield'  => $r->historical_yield_tons_ha ? number_format($r->historical_yield_tons_ha, 2) : '—',
                    'Predicted Yield'   => $latest ? number_format($latest->predicted_yield_tons_ha, 2) : '—',
                    'Predicted Class'   => $latest->predicted_class ?? '—',
                    'Actual Yield'      => $r->actual_yield_tons_ha ? number_format($r->actual_yield_tons_ha, 2) : '—',
                    'Status'            => $r->status,
                ];
            });
    }

    public function predictions(): Collection
    {
        return $this->latestPredictions()
            ->map(function ($p) {
                $r = $p->farmRecord;
                return [
                    'Farm'              => $r->farm->name ?? '—',
                    'Variety'           => $r->riceVariety->name ?? '—',
                    'Season'            => $r->season ?? '—',
                    'Year'              => $r->year ?? '—',
                    'Predicted Yield'   => number_format($p->predicted_yield_tons_ha, 2),
                    'Class'             => $p->predicted_class ?? '—',
                    'Confidence'        => $p->confidence ? number_format($p->confidence * 100, 1) . '%' : '—',
                    'Model'             => $p->model_type,
                    'Generated'         => $p->created_at?->format('M d, Y H:i') ?? '—',
                ];
            });
    }

    // ═══════════════════════════════════════════════════════════
    // AGGREGATES
    // ═══════════════════════════════════════════════════════════

    public function byBarangay(): Collection
    {
        return Farm::select(
                'barangay',
                DB::raw('COUNT(*) as farm_count'),
                DB::raw('SUM(land_area_ha) as total_area')
            )
            ->groupBy('barangay')
            ->orderBy('barangay')
            ->get()
            ->map(function ($row) {
                $farmIds = Farm::where('barangay', $row->barangay)->pluck('id');
                $records = FarmRecord::whereIn('farm_id', $farmIds)->count();
                $latest  = Prediction::whereIn('farm_record_id',
                                FarmRecord::whereIn('farm_id', $farmIds)->pluck('id'))
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->unique('farm_record_id');
                $avgYield = $latest->avg('predicted_yield_tons_ha');

                return [
                    'Barangay'         => $row->barangay,
                    'Farms'            => $row->farm_count,
                    'Total Area (ha)'  => number_format($row->total_area, 2),
                    'Farm Records'     => $records,
                    'Avg Predicted Yield' => $avgYield ? number_format($avgYield, 2) : '—',
                ];
            });
    }

    public function byVariety(): Collection
    {
        return RiceVariety::withCount(['farmRecords'])
            ->orderBy('name')
            ->get()
            ->map(function ($v) {
                $latest = Prediction::whereIn('farm_record_id',
                                FarmRecord::where('rice_variety_id', $v->id)->pluck('id'))
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->unique('farm_record_id');
                $avgYield = $latest->avg('predicted_yield_tons_ha');

                return [
                    'Variety'         => $v->name,
                    'Classification'  => $v->classification,
                    'Records'         => $v->farm_records_count,
                    'Avg Yield (t/ha)' => $v->avg_yield_transplanted ? number_format($v->avg_yield_transplanted, 2) : '—',
                    'Max Yield (t/ha)' => $v->max_yield_transplanted ? number_format($v->max_yield_transplanted, 2) : '—',
                    'Avg Predicted'   => $avgYield ? number_format($avgYield, 2) : '—',
                ];
            });
    }

    // ═══════════════════════════════════════════════════════════
    // ACCURACY & RANKINGS
    // ═══════════════════════════════════════════════════════════

    /**
     * Compare predicted vs actual on harvested records.
     */
    public function predictionAccuracy(): array
    {
        $harvested = FarmRecord::where('status', 'Harvested')
            ->whereNotNull('actual_yield_tons_ha')
            ->with(['predictions' => fn($q) => $q->orderBy('created_at', 'desc')])
            ->get();

        $matched = 0;
        $close   = 0;
        $off     = 0;
        $errors  = [];

        foreach ($harvested as $r) {
            $pred = $r->predictions->first();
            if (!$pred || !$pred->predicted_yield_tons_ha) continue;

            $diff = abs($pred->predicted_yield_tons_ha - $r->actual_yield_tons_ha);
            $pct  = $r->actual_yield_tons_ha > 0
                ? ($diff / $r->actual_yield_tons_ha) * 100
                : 0;

            $errors[] = $pct;

            if ($pct <= 10)      $matched++;
            elseif ($pct <= 25)  $close++;
            else                 $off++;
        }

        $total = count($errors);

        return [
            'total_compared' => $total,
            'matched'        => $matched,
            'close'          => $close,
            'off'            => $off,
            'avg_error_pct'  => $total > 0 ? round(array_sum($errors) / $total, 2) : null,
            'accuracy_pct'   => $total > 0 ? round(($matched / $total) * 100, 1) : null,
        ];
    }

    public function topFarms(int $limit = 10): Collection
    {
        return $this->latestPredictions()
            ->sortByDesc('predicted_yield_tons_ha')
            ->take($limit)
            ->map(function ($p) {
                $r = $p->farmRecord;
                return [
                    'Farm'      => $r->farm->name ?? '—',
                    'Barangay'  => $r->farm->barangay ?? '—',
                    'Farmer'    => $r->farm->user->name ?? '—',
                    'Variety'   => $r->riceVariety->name ?? '—',
                    'Yield'     => number_format($p->predicted_yield_tons_ha, 2) . ' t/ha',
                    'Class'     => $p->predicted_class ?? '—',
                ];
            })->values();
    }

    public function lowFarms(int $limit = 10): Collection
    {
        return $this->latestPredictions()
            ->sortBy('predicted_yield_tons_ha')
            ->take($limit)
            ->map(function ($p) {
                $r = $p->farmRecord;
                return [
                    'Farm'      => $r->farm->name ?? '—',
                    'Barangay'  => $r->farm->barangay ?? '—',
                    'Farmer'    => $r->farm->user->name ?? '—',
                    'Variety'   => $r->riceVariety->name ?? '—',
                    'Yield'     => number_format($p->predicted_yield_tons_ha, 2) . ' t/ha',
                    'Class'     => $p->predicted_class ?? '—',
                ];
            })->values();
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    private function latestPredictions(): Collection
    {
        return Prediction::with(['farmRecord.farm.user', 'farmRecord.riceVariety'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('farm_record_id');
    }
}