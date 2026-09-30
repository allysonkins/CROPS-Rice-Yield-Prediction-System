<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\FullReportExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected ReportService $service;

    public function __construct(ReportService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->build();

        return view('admin.reports.index', [
            'overview' => $data['overview'],
            'accuracy' => $data['accuracy'],
            'topFarms' => $data['top_farms'],
            'lowFarms' => $data['low_farms'],
        ]);
    }

    public function generatePdf()
    {
        $data = $this->service->build();

        log_activity('report', 'PDF report generated', null, [
            'file' => 'crops_yield_report_' . now()->format('Y-m-d') . '.pdf',
        ]);

        $pdf = Pdf::loadView('admin.reports.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('crops_yield_report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateExcel()
    {
        log_activity('report', 'Excel report generated', null, [
            'file' => 'crops_yield_report_' . now()->format('Y-m-d') . '.xlsx',
        ]);

        return Excel::download(
            new FullReportExport($this->service),
            'crops_yield_report_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Backward-compat — old route name redirects to PDF.
     */
    public function generate()
    {
        return $this->generatePdf();
    }
}