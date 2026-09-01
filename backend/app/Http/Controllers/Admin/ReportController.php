<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Prediction;
use App\Models\RiceVariety;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index()
    {
        $stats = [
            'total_farmers' => User::where('role', 'farmer')->count(),
            'total_farms' => Farm::count(),
            'total_varieties' => RiceVariety::count(),
            'avg_yield' => Prediction::where('model_type', 'Ensemble')->avg('predicted_yield_tons_ha') ?? 'N/A',
            'total_predictions' => Prediction::count(),
            'low_yield_count' => Prediction::where('model_type', 'Ensemble')
                ->where('predicted_yield_tons_ha', '<', 4.0)
                ->count(),
        ];

        return view('admin.reports.index', compact('stats'));
    }

    /**
     * Generate and download PDF report.
     */
    public function generate()
    {
        $data = [
            'farms' => Farm::with(['user', 'farmRecords.predictions'])->get(),
            'generated_at' => now(),
            'stats' => [
                'total_farmers' => User::where('role', 'farmer')->count(),
                'total_farms' => Farm::count(),
                'total_varieties' => RiceVariety::count(),
                'avg_yield' => Prediction::where('model_type', 'Ensemble')->avg('predicted_yield_tons_ha') ?? 'N/A',
            ],
        ];

        $pdf = Pdf::loadView('admin.reports.pdf', $data);
        return $pdf->download('crops_yield_report_' . now()->format('Y-m-d') . '.pdf');
    }
}