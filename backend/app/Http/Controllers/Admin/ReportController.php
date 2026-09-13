<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Prediction;
use App\Models\RiceVariety;
use App\Models\User;
use App\Models\FarmRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index()
    {
        // --- Get latest Random Forest prediction per farm record ---
        $allPredictions = Prediction::with(['farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest')
            ->orderBy('created_at', 'desc')
            ->get();

        $latestPredictions = $allPredictions->unique('farm_record_id');

        $avgYield = $latestPredictions->avg('predicted_yield_tons_ha');

        // Fallback: harvested actual yields
        if ($avgYield === null) {
            $avgYield = FarmRecord::where('status', 'Harvested')
                ->whereNotNull('actual_yield_tons_ha')
                ->avg('actual_yield_tons_ha');
        }

        // --- Low yield count: below 70% of variety's max ---
        $lowYieldCount = $latestPredictions->filter(function ($pred) {
            $farmRecord = $pred->farmRecord;
            if (!$farmRecord || !$farmRecord->riceVariety) {
                return false;
            }
            $max = $farmRecord->riceVariety->getMaxYieldForMethod($farmRecord->seeding_method);
            if ($max === null || $max <= 0) {
                return $pred->predicted_yield_tons_ha < 4.0;
            }
            return ($pred->predicted_yield_tons_ha / $max) < 0.7;
        })->count();

        $stats = [
            'total_farmers'      => User::where('role', 'farmer')->count(),
            'total_farms'        => Farm::count(),
            'total_varieties'    => RiceVariety::count(),
            'avg_yield'          => $avgYield !== null ? number_format($avgYield, 2) : 'N/A',
            'total_predictions'  => Prediction::where('model_type', 'RandomForest')->count(),
            'low_yield_count'    => $lowYieldCount,
        ];

        return view('admin.reports.index', compact('stats'));
    }

    /**
     * Generate and download the PDF report.
     */
    public function generate()
    {
        $allPredictions = Prediction::with(['farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest')
            ->orderBy('created_at', 'desc')
            ->get();

        $latestPredictions = $allPredictions->unique('farm_record_id');
        $avgYield = $latestPredictions->avg('predicted_yield_tons_ha');

        if ($avgYield === null) {
            $avgYield = FarmRecord::where('status', 'Harvested')
                ->whereNotNull('actual_yield_tons_ha')
                ->avg('actual_yield_tons_ha');
        }

        $data = [
            'farms' => Farm::with(['user', 'farmRecords.predictions', 'farmRecords.riceVariety'])->get(),
            'generated_at' => now(),
            'stats' => [
                'total_farmers'   => User::where('role', 'farmer')->count(),
                'total_farms'     => Farm::count(),
                'total_varieties' => RiceVariety::count(),
                'avg_yield'       => $avgYield !== null ? round($avgYield, 2) : null,
            ],
        ];

        log_activity('report', 'Report generated', null, [
            'file' => 'crops_yield_report_' . now()->format('Y-m-d') . '.pdf',
        ]);

        $pdf = Pdf::loadView('admin.reports.pdf', $data);
        return $pdf->download('crops_yield_report_' . now()->format('Y-m-d') . '.pdf');
    }
}