<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\FullReportExport;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\RiceVariety;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected ReportService $service;

    public const SECTIONS = [
        'overview'           => 'Executive Overview',
        'class_distribution' => 'Prediction Class Distribution',
        'by_barangay'        => 'Summary by Barangay',
        'by_variety'         => 'Summary by Rice Variety',
        'top_farms'          => 'Top 10 Performing Farms',
        'low_farms'          => 'Farms Needing Attention',
        'farm_records'       => 'All Farm Records',
        'predictions'        => 'All Predictions',
        'farmers'            => 'Farmers Registry',
        'farms'              => 'Farms Registry',
    ];

    public function __construct(ReportService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $farmers   = User::where('role', 'farmer')->orderBy('name')->get(['id', 'name', 'barangay']);
        $varieties = RiceVariety::orderBy('name')->get(['id', 'name']);
        $barangays = Farm::select('barangay')->distinct()->orderBy('barangay')
                        ->pluck('barangay')->filter()->values();

        $years = FarmRecord::select('year')
                    ->distinct()->orderBy('year', 'desc')
                    ->pluck('year')->filter()->values();

        return view('admin.reports.index', [
            'sections'  => self::SECTIONS,
            'farmers'   => $farmers,
            'varieties' => $varieties,
            'barangays' => $barangays,
            'years'     => $years,
        ]);
    }

    public function generatePdf(Request $request)
    {
        $filters  = $this->extractFilters($request);
        $sections = $this->extractSections($request);

        $data = $this->service
            ->withFilters($filters)
            ->withSections($sections)
            ->build();

        $displayFilters = $this->decorateFilters($filters);

        $data['sections']       = $sections;
        $data['section_labels'] = self::SECTIONS;
        $data['filters']        = $displayFilters;

        log_activity('report', 'PDF report generated', null, [
            'file'     => 'crops_report_' . now()->format('Y-m-d') . '.pdf',
            'filters'  => $filters,
            'sections' => $sections,
        ]);

        $pdf = Pdf::loadView('admin.reports.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('crops_report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateExcel(Request $request)
    {
        $filters  = $this->extractFilters($request);
        $sections = $this->extractSections($request);

        log_activity('report', 'Excel report generated', null, [
            'file'     => 'crops_report_' . now()->format('Y-m-d') . '.xlsx',
            'filters'  => $filters,
            'sections' => $sections,
        ]);

        return Excel::download(
            new FullReportExport($this->service, $filters, $sections),
            'crops_report_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function generate(Request $request)
    {
        return $this->generatePdf($request);
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    protected function extractFilters(Request $request): array
    {
        return array_filter([
            'year_from'    => $request->input('year_from'),
            'year_to'      => $request->input('year_to'),
            'season'       => $request->input('season'),
            'status'       => $request->input('status'),
            'water_source' => $request->input('water_source'),
            'variety_id'   => $request->input('variety_id'),
            'farmer_id'    => $request->input('farmer_id'),
            'barangay'     => $request->input('barangay'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    protected function decorateFilters(array $filters): array
    {
        if (!empty($filters['variety_id'])) {
            $filters['variety_name'] = RiceVariety::find($filters['variety_id'])?->name;
        }
        if (!empty($filters['farmer_id'])) {
            $filters['farmer_name'] = User::find($filters['farmer_id'])?->name;
        }
        return $filters;
    }

    protected function extractSections(Request $request): array
    {
        $all = array_keys(self::SECTIONS);
        $requested = $request->input('sections', []);

        if (!is_array($requested) || empty($requested)) {
            return $all;
        }

        return array_values(array_intersect($all, $requested));
    }
}