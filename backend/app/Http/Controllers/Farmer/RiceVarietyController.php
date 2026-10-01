<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\RiceVariety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RiceVarietyController extends Controller
{
    /**
     * Read-only variety catalog, farmer-facing.
     * Layout mirrors the admin view (same cards, same header, same filters)
     * but without add/edit/delete controls.
     */
    public function index(Request $request)
    {
        $query = RiceVariety::query()->orderBy('name');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($class = $request->input('classification')) {
            $query->where('classification', $class);
        }

        $varieties        = $query->get();
        $trainedVarieties = $this->getTrainedVarieties();
        $lastTrained      = $this->getLastTrainedDate();

        return view('farmer.rice-varieties.index', compact(
            'varieties',
            'trainedVarieties',
            'lastTrained'
        ));
    }

    public function show($id)
    {
        if (!request()->ajax() && !request()->wantsJson()) {
            return redirect()->route('farmer.rice-varieties.index');
        }

        $variety = RiceVariety::findOrFail($id);

        return view('farmer.rice-varieties.partials.detail', compact('variety'));
    }

    // ═══════════════════════════════════════════════════════════
    // ML MODEL HELPERS (mirrors Admin\RiceVarietyController)
    // ═══════════════════════════════════════════════════════════

    /**
     * Trained variety list.
     * Priority:
     *   1. config('santiago.ml_trained_varieties')  ← hardcoded fallback
     *   2. Render ML service over HTTP (needs /features endpoint)
     *   3. Local feature_legend.json (dev only)
     */
    private function getTrainedVarieties(): array
    {
        // 1. Hardcoded config (works on Hostinger without any endpoint)
        $hardcoded = config('santiago.ml_trained_varieties', []);
        if (!empty($hardcoded)) {
            return $hardcoded;
        }

        // 2 & 3. Remote / local (cached 6h)
        return Cache::remember('ml.trained_varieties', now()->addHours(6), function () {
            $remote = $this->fetchTrainedVarietiesFromService();
            if (!empty($remote)) {
                return $remote;
            }

            $path = $this->findMlFile('feature_legend.json');
            if (!$path) return [];

            $legend = json_decode(file_get_contents($path), true) ?? [];
            return $this->extractVarietyNames($legend);
        });
    }

    /**
     * Query the Render ML service for its feature legend.
     * Only works if you add a /features endpoint to the Flask app.
     */
    private function fetchTrainedVarietiesFromService(): array
    {
        $base = rtrim((string) config('services.ml.url', ''), '/');
        if ($base === '') return [];

        $endpoint = (string) config('services.ml.endpoint', '/features');
        $timeout  = (int)    config('services.ml.timeout', 10);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($base . $endpoint);

            if (!$response->ok()) {
                return [];
            }

            $data = $response->json();
            if (!is_array($data)) return [];

            if (isset($data['features']) && is_array($data['features'])) {
                return $this->extractVarietyNames($data['features']);
            }
            if (isset($data['feature_legend']) && is_array($data['feature_legend'])) {
                return $this->extractVarietyNames($data['feature_legend']);
            }
            if (isset($data['feature_names']) && is_array($data['feature_names'])) {
                return $this->extractVarietyNames($data['feature_names']);
            }
            if (isset($data['varieties']) && is_array($data['varieties'])) {
                return array_values(array_unique($data['varieties']));
            }

            return $this->extractVarietyNames($data);

        } catch (\Throwable $e) {
            return [];
        }
    }

    private function extractVarietyNames($legend): array
    {
        if (!is_array($legend)) return [];

        $keys     = array_keys($legend);
        $isList   = $keys === range(0, count($keys) - 1);
        $features = $isList ? array_values($legend) : $keys;

        $numeric = [
            'variety_maturity_days',
            'variety_max_yield',
            'variety_avg_yield',
        ];

        $out = [];
        foreach ($features as $feat) {
            if (!is_string($feat)) continue;
            if (!str_starts_with($feat, 'variety_')) continue;
            if (in_array($feat, $numeric, true)) continue;
            $out[] = substr($feat, strlen('variety_'));
        }

        return array_values(array_unique($out));
    }

    private function getLastTrainedDate(): string
    {
        // The /health endpoint gives us model info
        $base = rtrim((string) config('services.ml.url', ''), '/');
        if ($base !== '') {
            try {
                $response = Http::timeout(3)
                    ->acceptJson()
                    ->get($base . '/health');

                if ($response->ok()) {
                    $info = $response->json();
                    $ts   = $info['trained_at'] ?? $info['last_trained'] ?? null;
                    if ($ts) {
                        return date('M d, Y g:i A', strtotime($ts));
                    }
                    if (isset($info['version'])) {
                        return 'Model v' . $info['version'];
                    }
                }
            } catch (\Throwable $e) {
                // silent
            }
        }

        $path = $this->findMlFile('model_rf.pkl');
        if (!$path) return 'Not trained yet';

        return date('M d, Y g:i A', filemtime($path));
    }

    private function findMlFile(string $filename): ?string
    {
        $candidates = [
            base_path('ml-service/' . $filename),
            base_path('../ml-service/' . $filename),
            base_path('../../ml-service/' . $filename),
            'C:/xampp/install/htdocs/crops-system/ml-service/' . $filename,
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }

        return null;
    }
}