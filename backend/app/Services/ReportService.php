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
    protected array $filters  = [];
    protected array $sections = [];

    public function withFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    public function withSections(array $sections): self
    {
        $this->sections = $sections;
        return $this;
    }

    public function build(): array
    {
        return [
            'generated_at' => now(),
            'overview'     => $this->overview(),
            'by_barangay'  => $this->byBarangay(),
            'by_variety'   => $this->byVariety(),
            'top_farms'    => $this->topFarms(),
            'low_farms'    => $this->lowFarms(),
            'farm_records' => $this->farmRecords(),
            'predictions'  => $this->predictions(),
            'farmers'      => $this->farmers(),
            'farms'        => $this->farms(),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // FILTER-AWARE QUERY BUILDERS
    // ═══════════════════════════════════════════════════════════

    protected function farmRecordQuery()
    {
        return FarmRecord::query()
            ->when($this->filters['year_from'] ?? null,   fn ($q, $v) => $q->where('year', '>=', (int) $v))
            ->when($this->filters['year_to']   ?? null,   fn ($q, $v) => $q->where('year', '<=', (int) $v))
            ->when($this->filters['season']    ?? null,   fn ($q, $v) => $q->where('season', 'like', "%{$v}%"))
            ->when($this->filters['status']    ?? null,   fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['water_source'] ?? null, fn ($q, $v) => $q->where('water_source', $v))
            ->when($this->filters['variety_id'] ?? null,  fn ($q, $v) => $q->where('rice_variety_id', $v))
            ->when($this->filters['barangay'] ?? null,    fn ($q, $v) => $q->whereHas('farm', fn ($q2) => $q2->where('barangay', $v)))
            ->when($this->filters['farmer_id'] ?? null,   fn ($q, $v) => $q->whereHas('farm', fn ($q2) => $q2->where('user_id', $v)));
    }

    protected function farmQuery()
    {
        return Farm::query()
            ->when($this->filters['barangay'] ?? null,  fn ($q, $v) => $q->where('barangay', $v))
            ->when($this->filters['farmer_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v));
    }

    protected function farmerQuery()
    {
        return User::where('role', 'farmer')
            ->when($this->filters['barangay'] ?? null,  fn ($q, $v) => $q->where('barangay', $v))
            ->when($this->filters['farmer_id'] ?? null, fn ($q, $v) => $q->where('id', $v));
    }

    protected function predictionQuery()
    {
        return Prediction::query()
            ->where('model_type', 'XGBoost')
            ->whereHas('farmRecord', function ($q) {
                $q->when($this->filters['year_from'] ?? null, fn ($q2, $v) => $q2->where('year', '>=', (int) $v))
                  ->when($this->filters['year_to']   ?? null, fn ($q2, $v) => $q2->where('year', '<=', (int) $v))
                  ->when($this->filters['season']    ?? null, fn ($q2, $v) => $q2->where('season', 'like', "%{$v}%"))
                  ->when($this->filters['status']    ?? null, fn ($q2, $v) => $q2->where('status', $v))
                  ->when($this->filters['water_source'] ?? null, fn ($q2, $v) => $q2->where('water_source', $v))
                  ->when($this->filters['variety_id'] ?? null, fn ($q2, $v) => $q2->where('rice_variety_id', $v))
                  ->when($this->filters['barangay'] ?? null,  fn ($q2, $v) => $q2->whereHas('farm', fn ($q3) => $q3->where('barangay', $v)))
                  ->when($this->filters['farmer_id'] ?? null, fn ($q2, $v) => $q2->whereHas('farm', fn ($q3) => $q3->where('user_id', $v)));
            });
    }

    // ═══════════════════════════════════════════════════════════
    // OVERVIEW
    // ═══════════════════════════════════════════════════════════

    public function overview(): array
    {
        $latest = $this->latestPredictions();

        $avgPredicted = $latest->avg('predicted_yield_tons_ha');

        $harvestedQuery = $this->farmRecordQuery()
            ->where('status', 'Harvested')
            ->whereNotNull('actual_yield_tons_ha');
        $avgActual = $harvestedQuery->avg('actual_yield_tons_ha');

        $totalArea = $this->farmQuery()->sum('land_area_ha');

        return [
            'total_farmers'       => $this->farmerQuery()->count(),
            'verified_farmers'    => $this->farmerQuery()->whereNotNull('verified_by_cao_at')->count(),
            'pending_farmers'     => $this->farmerQuery()->whereNull('verified_by_cao_at')->count(),
            'total_farms'         => $this->farmQuery()->count(),
            'total_area_ha'       => round($totalArea, 2),
            'total_varieties'     => RiceVariety::count(),
            'total_farm_records'  => $this->farmRecordQuery()->count(),
            'vegetative_records'  => $this->farmRecordQuery()->where('status', 'Vegetative')->count(),
            'harvested_records'   => $this->farmRecordQuery()->where('status', 'Harvested')->count(),
            'total_predictions'   => $this->predictionQuery()->count(),
            'avg_predicted_yield' => $avgPredicted !== null ? round($avgPredicted, 2) : null,
            'avg_actual_yield'    => $avgActual !== null ? round($avgActual, 2) : null,
            'yield_class_counts'  => $this->classDistribution($latest),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // DETAIL SHEETS
    // ═══════════════════════════════════════════════════════════

    public function farmers(): Collection
    {
        return $this->farmerQuery()
            ->withCount('farms')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'Name'         => $u->name,
                'RSBSA Number' => $u->rsbsa_number ?? '—',
                'Phone'        => $u->phone ?? '—',
                'Barangay'     => $u->barangay ?? '—',
                'Farms'        => $u->farms_count,
                'Verified'     => $u->verified_by_cao_at ? 'Yes' : 'Pending',
                'Registered'   => $u->created_at?->format('M d, Y') ?? '—',
            ]);
    }

    public function farms(): Collection
    {
        return $this->farmQuery()
            ->with(['user', 'farmRecords'])
            ->withCount('farmRecords')
            ->orderBy('name')
            ->get()
            ->map(fn ($f) => [
                'Farm Name' => $f->name,
                'Barangay'  => $f->barangay,
                'Farmer'    => $f->user->name ?? 'Unassigned',
                'Area (ha)' => number_format($f->land_area_ha, 2),
                'Soil Type' => $f->soil_type ?? '—',
                'Records'   => $f->farm_records_count,
                'Latitude'  => $f->latitude ?? '—',
                'Longitude' => $f->longitude ?? '—',
            ]);
    }

    public function farmRecords(): Collection
    {
        return $this->farmRecordQuery()
            ->with(['farm', 'riceVariety', 'predictions'])
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
                    'Water Source'      => $r->water_source ?? '—',
                    'Fertilizer (kg/ha)' => number_format($r->fertilizer_kg_ha, 1),
                    'Historical Yield'  => $r->historical_yield_tons_ha ? number_format($r->historical_yield_tons_ha, 2) : '—',
                    'Predicted Yield'   => $latest?->predicted_yield_tons_ha !== null
                                                ? number_format($latest->predicted_yield_tons_ha, 2)
                                                : '—',
                    'Predicted Class'   => $this->classifyPrediction($latest) ?? '—',
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
                    'Farm'            => $r->farm->name ?? '—',
                    'Variety'         => $r->riceVariety->name ?? '—',
                    'Season'          => $r->season ?? '—',
                    'Year'            => $r->year ?? '—',
                    'Predicted Yield' => number_format($p->predicted_yield_tons_ha, 2),
                    'Class'           => $this->classifyPrediction($p) ?? '—',
                    'Confidence'      => $p->confidence ? number_format($p->confidence * 100, 1) . '%' : '—',
                    'Model'           => $p->model_type,
                    'Generated'       => $p->created_at?->format('M d, Y H:i') ?? '—',
                ];
            });
    }

    // ═══════════════════════════════════════════════════════════
    // AGGREGATES
    // ═══════════════════════════════════════════════════════════

    public function byBarangay(): Collection
    {
        return $this->farmQuery()
            ->select('barangay', DB::raw('COUNT(*) as farm_count'), DB::raw('SUM(land_area_ha) as total_area'))
            ->groupBy('barangay')
            ->orderBy('barangay')
            ->get()
            ->map(function ($row) {
                $farmIds = $this->farmQuery()->where('barangay', $row->barangay)->pluck('id');

                $recordIds = $this->farmRecordQuery()
                    ->whereIn('farm_id', $farmIds)
                    ->pluck('id');

                $latest = Prediction::whereIn('farm_record_id', $recordIds)
                            ->where('model_type', 'XGBoost')
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->unique('farm_record_id');

                $avgYield = $latest->avg('predicted_yield_tons_ha');

                return [
                    'Barangay'            => $row->barangay,
                    'Farms'               => $row->farm_count,
                    'Total Area (ha)'     => number_format($row->total_area, 2),
                    'Farm Records'        => count($recordIds),
                    'Avg Predicted Yield' => $avgYield ? number_format($avgYield, 2) : '—',
                ];
            });
    }

    public function byVariety(): Collection
    {
        return RiceVariety::orderBy('name')
            ->get()
            ->map(function ($v) {
                $recordIds = $this->farmRecordQuery()
                    ->where('rice_variety_id', $v->id)
                    ->pluck('id');

                $latest = Prediction::whereIn('farm_record_id', $recordIds)
                            ->where('model_type', 'XGBoost')
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->unique('farm_record_id');

                $avgYield = $latest->avg('predicted_yield_tons_ha');

                return [
                    'Variety'          => $v->name,
                    'Classification'   => $v->classification,
                    'Records'          => count($recordIds),
                    'Avg Yield (t/ha)' => $v->avg_yield_transplanted ? number_format($v->avg_yield_transplanted, 2) : '—',
                    'Max Yield (t/ha)' => $v->max_yield_transplanted ? number_format($v->max_yield_transplanted, 2) : '—',
                    'Avg Predicted'    => $avgYield ? number_format($avgYield, 2) : '—',
                ];
            })
            ->filter(fn ($v) => $v['Records'] > 0)
            ->values();
    }

    // ═══════════════════════════════════════════════════════════
    // RANKINGS
    // ═══════════════════════════════════════════════════════════

    public function topFarms(int $limit = 10): Collection
    {
        return $this->latestPredictions()
            ->sortByDesc('predicted_yield_tons_ha')
            ->take($limit)
            ->map(function ($p) {
                $r = $p->farmRecord;
                return [
                    'Farm'     => $r->farm->name ?? '—',
                    'Barangay' => $r->farm->barangay ?? '—',
                    'Farmer'   => $r->farm->user->name ?? '—',
                    'Variety'  => $r->riceVariety->name ?? '—',
                    'Yield'    => number_format($p->predicted_yield_tons_ha, 2) . ' t/ha',
                    'Class'    => $this->classifyPrediction($p) ?? '—',
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
                    'Farm'     => $r->farm->name ?? '—',
                    'Barangay' => $r->farm->barangay ?? '—',
                    'Farmer'   => $r->farm->user->name ?? '—',
                    'Variety'  => $r->riceVariety->name ?? '—',
                    'Yield'    => number_format($p->predicted_yield_tons_ha, 2) . ' t/ha',
                    'Class'    => $this->classifyPrediction($p) ?? '—',
                ];
            })->values();
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    private function latestPredictions(): Collection
    {
        return $this->predictionQuery()
            ->with(['farmRecord.farm.user', 'farmRecord.riceVariety'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('farm_record_id');
    }

    private function classifyPrediction(?Prediction $p): ?string
    {
        if (!$p) return null;
        $yield = $p->predicted_yield_tons_ha;
        if ($yield === null) return null;

        $fr = $p->farmRecord;
        if (!$fr || !$fr->riceVariety) return null;

        $method = $fr->seeding_method ?? 'Transplanted';
        $vy     = $fr->riceVariety->getYieldForMethod($method);
        $avg    = $vy->avg ?? ($fr->riceVariety->avg_yield ?? null);

        if (!$avg || $avg <= 0) return null;
        $ratio = $yield / $avg;

        if ($ratio >= 1.125) return 'High';
        if ($ratio >= 0.875) return 'Medium';
        return 'Low';
    }

    private function classDistribution(Collection $predictions): array
    {
        $counts = ['High' => 0, 'Medium' => 0, 'Low' => 0];
        foreach ($predictions as $p) {
            $class = $this->classifyPrediction($p);
            if ($class !== null && isset($counts[$class])) $counts[$class]++;
        }
        return $counts;
    }
}