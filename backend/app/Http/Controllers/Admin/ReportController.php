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
    public function index()
    {
        $stats = [
            'total_farmers' => User::where('role', 'farmer')->count(),
            'total_farms' => Farm::count(),
            'total_varieties' => RiceVariety::count(),
            'avg_yield' => Prediction::where('model_type', 'Ensemble')->avg('predicted_yield_tons_ha'),
            'low_yield_count' => Prediction::where('model_type', 'Ensemble')
                ->where('predicted_yield_tons_ha', '<', 4.0)->count(),
        ];

        return view('admin.reports.index', compact('stats'));
    }

    public function generate()
    {
        $data = [
            'farms' => Farm::with(['user', 'farmRecords', 'farmRecords.predictions'])->get(),
            'generated_at' => now(),
            'stats' => [
                'total_farmers' => User::where('role', 'farmer')->count(),
                'total_farms' => Farm::count(),
                'total_varieties' => RiceVariety::count(),
                'avg_yield' => Prediction::where('model_type', 'Ensemble')->avg('predicted_yield_tons_ha'),
            ]
        ];

        $pdf = Pdf::loadView('admin.reports.pdf', $data);
        return $pdf->download('crops_yield_report_' . now()->format('Y-m-d') . '.pdf');
    }
}