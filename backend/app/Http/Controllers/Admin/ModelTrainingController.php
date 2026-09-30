<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModelTrainingRun;
use App\Services\ModelTrainingService;
use Illuminate\Http\Request;

class ModelTrainingController extends Controller
{
    protected ModelTrainingService $service;

    public function __construct(ModelTrainingService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $stats      = $this->service->getTrainingStats();
        $recentRuns = ModelTrainingRun::with('triggeredBy')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.ml.index', compact('stats', 'recentRuns'));
    }

    public function history()
    {
        $runs = ModelTrainingRun::with('triggeredBy')->latest()->paginate(20);
        return view('admin.ml.history', compact('runs'));
    }

    public function show($id)
    {
        $run = ModelTrainingRun::with('triggeredBy')->findOrFail($id);
        return view('admin.ml.show', compact('run'));
    }

    public function start(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Only admins can retrain the model.'], 403);
        }

        try {
            $run = $this->service->startTraining(auth()->id());

            log_activity('ml', 'Model retraining started', $run, [
                'version' => $run->model_version,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Training started. This may take 1–5 minutes.',
                'run_id'  => $run->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function status($id)
    {
        $run = ModelTrainingRun::findOrFail($id);
        $run = $this->service->checkRun($run);

        return response()->json([
            'success'          => true,
            'status'           => $run->status,
            'progress_log'     => $run->log_output,
            'overall_accuracy' => $run->overall_accuracy,
            'training_samples' => $run->training_samples,
            'test_samples'     => $run->test_samples,
            'real_samples'     => $run->real_samples,
            'synthetic_samples'=> $run->synthetic_samples,
            'duration_seconds' => $run->duration_seconds,
            'error_message'    => $run->error_message,
            'completed_at'     => $run->completed_at?->toIso8601String(),
        ]);
    }
}