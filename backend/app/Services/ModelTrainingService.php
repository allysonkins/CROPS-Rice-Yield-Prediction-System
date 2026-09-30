<?php

namespace App\Services;

use App\Models\FarmRecord;
use App\Models\ModelTrainingRun;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ModelTrainingService
{
    /**
     * Snapshot of the current state — used by the dashboard.
     */
    public function getTrainingStats(): array
    {
        $total = FarmRecord::whereNotNull('actual_yield_tons_ha')->count();

        // Guard each optional column so a missing one doesn't blow up the page
        $withWeather = 0;
        if (Schema::hasColumn('farm_records', 'weather_temperature')) {
            $withWeather = FarmRecord::whereNotNull('actual_yield_tons_ha')
                ->whereNotNull('weather_temperature')
                ->count();
        }

        $withHistory = 0;
        if (Schema::hasColumn('farm_records', 'historical_yield_tons_ha')) {
            $withHistory = FarmRecord::whereNotNull('actual_yield_tons_ha')
                ->whereNotNull('historical_yield_tons_ha')
                ->count();
        }

        $lastRun       = ModelTrainingRun::latest()->first();
        $lastCompleted = ModelTrainingRun::where('status', 'completed')
            ->latest('completed_at')
            ->first();
        $currentModel  = $this->getCurrentModel();
        $minSamples    = (int) config('ml.min_samples_to_train');

        return [
            'total_samples'  => $total,
            'with_weather'   => $withWeather,
            'with_history'   => $withHistory,
            'last_run'       => $lastRun,
            'last_completed' => $lastCompleted,
            'current_model'  => $currentModel,
            'can_train'      => $total >= $minSamples,
            'min_samples'    => $minSamples,
        ];
    }

    public function getCurrentModel(): ?array
    {
        $file = config('ml.current_pointer');
        if (!is_file($file)) return null;

        $json = json_decode(file_get_contents($file), true);
        return is_array($json) ? $json : null;
    }

    public function setCurrentModel(array $meta): void
    {
        $file = config('ml.current_pointer');
        @mkdir(dirname($file), 0755, true);
        file_put_contents($file, json_encode($meta, JSON_PRETTY_PRINT));
    }

    /**
     * Export all harvested farm records to CSV for the retrain script.
     */
    public function exportTrainingData(int $runId): string
    {
        $dir = config('ml.training_data_dir');
        @mkdir($dir, 0755, true);
        $path = "{$dir}/training_run_{$runId}.csv";

        $hasWeather  = Schema::hasColumn('farm_records', 'weather_temperature');
        $hasHumidity = Schema::hasColumn('farm_records', 'weather_humidity');
        $hasRainfall = Schema::hasColumn('farm_records', 'weather_rainfall');

        $fh = fopen($path, 'w');
        fputcsv($fh, [
            'farm_record_id', 'variety', 'classification', 'soil_type',
            'season', 'year', 'seeding_method', 'fertilizer_kg_ha',
            'land_area_ha', 'historical_yield_tons_ha',
            'temperature_avg', 'rainfall_mm', 'humidity_avg',
            'actual_yield_tons_ha',
        ]);

        $count = 0;

        FarmRecord::with(['farm', 'riceVariety'])
            ->whereNotNull('actual_yield_tons_ha')
            ->orderBy('id')
            ->chunk(200, function ($records) use ($fh, &$count, $hasWeather, $hasHumidity, $hasRainfall) {
                foreach ($records as $r) {
                    // Normalize season
                    $season = str_contains(strtolower((string) $r->season), 'dry') ? 'Dry' : 'Wet';

                    // Normalize seeding method
                    $seeding = 'Transplanted';
                    if ($r->seeding_method) {
                        $lower = strtolower($r->seeding_method);
                        if (str_contains($lower, 'direct')) $seeding = 'Direct Seeded';
                    }

                    // Weather: use snapshot if present, else Santiago defaults
                    $temp  = $hasWeather  ? ($r->weather_temperature ?? 27.5) : 27.5;
                    $rain  = $hasRainfall ? ($r->weather_rainfall    ?? 200.0) : 200.0;
                    $humid = $hasHumidity ? ($r->weather_humidity    ?? 78.0)  : 78.0;

                    fputcsv($fh, [
                        $r->id,
                        $r->riceVariety->name ?? 'Unknown',
                        $r->riceVariety->classification ?? 'Inbred',
                        $r->farm->soil_type ?? 'Loam',
                        $season,
                        $r->year,
                        $seeding,
                        $r->fertilizer_kg_ha,
                        $r->farm->land_area_ha ?? 1.0,
                        $r->historical_yield_tons_ha,
                        $temp,
                        $rain,
                        $humid,
                        $r->actual_yield_tons_ha,
                    ]);
                    $count++;
                }
            });

        fclose($fh);

        if ($count === 0) {
            throw new \RuntimeException('No harvested farm records with actual yield found.');
        }

        return $path;
    }

    /**
     * Kick off a new training run in the background.
     */
    public function startTraining(int $userId): ModelTrainingRun
    {
        $stats = $this->getTrainingStats();

        if (!$stats['can_train']) {
            throw new \RuntimeException(
                "Need at least {$stats['min_samples']} harvested records to train. " .
                "Currently have {$stats['total_samples']}."
            );
        }

        $run = ModelTrainingRun::create([
            'status'       => 'running',
            'triggered_by' => $userId,
            'started_at'   => now(),
            'model_version'=> now()->format('Y.m.d.His'),
        ]);

        $csvPath   = $this->exportTrainingData($run->id);
        $serviceDir = config('ml.service_dir');
        $runsDir   = config('ml.runs_dir') . "/{$run->id}";
        @mkdir($runsDir, 0755, true);

        $metaPath = "{$runsDir}/training_meta.json";
        $logPath  = "{$runsDir}/train.log";

        $python    = config('ml.python_binary');
        $script    = "{$serviceDir}/retrain.py";
        $synthetic = "{$serviceDir}/rice_yield_dataset.csv";

        if (!is_file($script)) {
            $run->update([
                'status'        => 'failed',
                'error_message' => "Retrain script not found at: {$script}",
                'completed_at'  => now(),
            ]);
            throw new \RuntimeException("Retrain script not found at: {$script}");
        }

        $currentPointer = storage_path('app/ml/current_model.json');
        $artifactsDir   = "{$serviceDir}/artifacts";
        @mkdir($artifactsDir, 0755, true);

        $parts = [
            escapeshellarg($python),
            escapeshellarg($script),
            '--input-csv',       escapeshellarg($csvPath),
            '--synthetic-csv',   escapeshellarg($synthetic),
            '--artifacts-dir',   escapeshellarg($artifactsDir),
            '--metadata',        escapeshellarg($metaPath),
            '--current-pointer', escapeshellarg($currentPointer),
            '--version',         escapeshellarg($run->model_version),
        ];
        $base = implode(' ', $parts);

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'start /B "" ' . $base . ' > ' . escapeshellarg($logPath) . ' 2>&1';
            pclose(popen($cmd, 'r'));
        } else {
            $cmd = 'nohup ' . $base . ' > ' . escapeshellarg($logPath) . ' 2>&1 &';
            exec($cmd);
        }

        $run->update([
            'log_output' => "Started at " . now()->toDateTimeString() . "\nCommand: {$base}\n",
        ]);

        Log::info('Model training started', [
            'run_id'  => $run->id,
            'version' => $run->model_version,
            'command' => $base,
        ]);

        return $run;
    }

    /**
     * Poll an in-progress run.
     */
    public function checkRun(ModelTrainingRun $run): ModelTrainingRun
    {
        if (in_array($run->status, ['completed', 'failed'], true)) {
            return $run;
        }

        $runDir       = config('ml.runs_dir') . "/{$run->id}";
        $metadataPath = "{$runDir}/training_meta.json";
        $logPath      = "{$runDir}/train.log";

        if (is_file($logPath)) {
            $run->log_output = mb_substr(file_get_contents($logPath), -20000);
        }

        if (is_file($metadataPath)) {
            $meta = json_decode(file_get_contents($metadataPath), true);

            if (!is_array($meta)) {
                $run->save();
                return $run;
            }

            if (isset($meta['error'])) {
                $run->status        = 'failed';
                $run->error_message = (string) $meta['error'];
            } else {
                $previous = $this->getCurrentModel();

                $run->status           = 'completed';
                $run->model_path       = $meta['model_path'] ?? null;
                $run->training_samples = (int) ($meta['training_samples'] ?? 0);
                $run->test_samples     = (int) ($meta['test_samples'] ?? 0);
                $run->real_samples     = (int) ($meta['real_samples'] ?? 0);
                $run->synthetic_samples= (int) ($meta['synthetic_samples'] ?? 0);
                $run->overall_accuracy = isset($meta['overall_accuracy'])
                    ? round((float) $meta['overall_accuracy'], 4)
                    : null;

                $run->metrics          = $meta['metrics'] ?? null;
                $run->previous_metrics = $previous ? [
                    'overall_accuracy' => $previous['overall_accuracy'] ?? null,
                    'version'          => $previous['version'] ?? null,
                ] : null;
            }

            $run->completed_at = now();
            if ($run->started_at) {
                $run->duration_seconds = max(0, $run->completed_at->diffInSeconds($run->started_at));
            }
        } else {
            if ($run->started_at && $run->started_at->diffInMinutes(now()) > 30) {
                $run->status        = 'failed';
                $run->error_message = 'Training timed out after 30 minutes (no metadata written).';
                $run->completed_at  = now();
            }
        }

        $run->save();
        return $run;
    }
}