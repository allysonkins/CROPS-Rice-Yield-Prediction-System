<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiceVariety;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RiceVarietyController extends Controller
{
    public function index()
    {
        $varieties        = RiceVariety::orderBy('name')->get();
        $trainedVarieties = $this->getTrainedVarieties();
        $lastTrained      = $this->getLastTrainedDate();

        return view('admin.rice-varieties.index', compact(
            'varieties',
            'trainedVarieties',
            'lastTrained'
        ));
    }

    public function create()
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot add rice varieties.');
        }
        return view('admin.rice-varieties.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->role === 'farmer') {
            return response()->json([
                'success' => false,
                'error'   => 'Farmers cannot add rice varieties.'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'name'                       => 'required|string|max:255|unique:rice_varieties',
                'classification'             => 'required|in:Hybrid,Inbred',
                'growth_period'              => 'nullable|integer|min:60|max:200',
                'growth_period_transplanted' => 'nullable|integer|min:60|max:200',
                'growth_period_direct'       => 'nullable|integer|min:60|max:200',
                'description'                => 'nullable|string',
                'avg_yield'                  => 'nullable|numeric|min:0|max:15',
                'max_yield'                  => 'nullable|numeric|min:0|max:15',
                'avg_yield_transplanted'     => 'nullable|numeric|min:0|max:15',
                'max_yield_transplanted'     => 'nullable|numeric|min:0|max:15',
                'avg_yield_direct'           => 'nullable|numeric|min:0|max:15',
                'max_yield_direct'           => 'nullable|numeric|min:0|max:15',
                'grain_quality'              => 'nullable|string',
                'disease_susceptibility'     => 'nullable|string|max:255',
                'optimal_temp_min'           => 'nullable|numeric|min:10|max:40',
                'optimal_temp_max'           => 'nullable|numeric|min:10|max:40',
                'resilience'                 => 'nullable|array',
            ]);

            $validated['growth_period'] = $validated['growth_period_transplanted']
                                       ?? $validated['growth_period_direct']
                                       ?? null;

            $variety = RiceVariety::create($validated);

            log_activity('created', 'Rice variety created', $variety, [
                'name'           => $variety->name,
                'classification' => $variety->classification,
            ]);

            // Bust the trained-varieties cache so the new one is re-evaluated
            $this->forgetTrainedVarietiesCache();

            $trainedVarieties = $this->getTrainedVarieties();
            $isFallback = !$this->isVarietyTrained($variety->name, $trainedVarieties);

            if ($request->ajax()) {
                return response()->json([
                    'success'     => true,
                    'message'     => $isFallback
                        ? '✅ Rice variety "' . $variety->name . '" added! Note: this variety is not in the ML model — predictions will use numeric features only. Retrain the model to include it.'
                        : '✅ Rice variety "' . $variety->name . '" added successfully!',
                    'data'        => $variety,
                    'is_fallback' => $isFallback,
                ], 201);
            }

            return redirect()->route('admin.rice-varieties.index')
                ->with('success', 'Rice variety added successfully!');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $e->errors(),
                    'error'   => 'Please correct the highlighted fields.'
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (QueryException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Database error: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Database error occurred.')->withInput();

        } catch (\Throwable $e) {   // ← was \Exception
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Failed to save variety: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to save variety.')->withInput();
        }
    }

    public function edit($id)
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot edit rice varieties.');
        }
        $variety = RiceVariety::findOrFail($id);
        return view('admin.rice-varieties.edit', compact('variety'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->role === 'farmer') {
            return response()->json([
                'success' => false,
                'error'   => 'Farmers cannot edit rice varieties.'
            ], 403);
        }

        try {
            $variety = RiceVariety::findOrFail($id);

            $validated = $request->validate([
                'name'                       => ['required', 'string', 'max:255', Rule::unique('rice_varieties')->ignore($variety->id)],
                'classification'             => 'required|in:Hybrid,Inbred',
                'growth_period'              => 'nullable|integer|min:60|max:200',
                'growth_period_transplanted' => 'nullable|integer|min:60|max:200',
                'growth_period_direct'       => 'nullable|integer|min:60|max:200',
                'description'                => 'nullable|string',
                'avg_yield'                  => 'nullable|numeric|min:0|max:15',
                'max_yield'                  => 'nullable|numeric|min:0|max:15',
                'avg_yield_transplanted'     => 'nullable|numeric|min:0|max:15',
                'max_yield_transplanted'     => 'nullable|numeric|min:0|max:15',
                'avg_yield_direct'           => 'nullable|numeric|min:0|max:15',
                'max_yield_direct'           => 'nullable|numeric|min:0|max:15',
                'grain_quality'              => 'nullable|string',
                'disease_susceptibility'     => 'nullable|string|max:255',
                'optimal_temp_min'           => 'nullable|numeric|min:10|max:40',
                'optimal_temp_max'           => 'nullable|numeric|min:10|max:40',
                'resilience'                 => 'nullable|array',
            ]);

            $validated['growth_period'] = $validated['growth_period_transplanted']
                                       ?? $validated['growth_period_direct']
                                       ?? $variety->growth_period
                                       ?? null;

            $oldData = $variety->only(['name', 'classification', 'description']);
            $variety->update($validated);

            log_activity('updated', 'Rice variety updated', $variety, [
                'old' => $oldData,
                'new' => $variety->only(['name', 'classification', 'description']),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Rice variety "' . $variety->name . '" updated successfully!',
                    'data'    => $variety
                ]);
            }

            return redirect()->route('admin.rice-varieties.index')
                ->with('success', 'Rice variety updated successfully!');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $e->errors(),
                    'error'   => 'Please correct the highlighted fields.'
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (QueryException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Database error: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Database error occurred.')->withInput();

        } catch (\Throwable $e) {   // ← was \Exception
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Failed to update variety: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to update variety.')->withInput();
        }
    }

    public function destroy($id)
    {
        if (auth()->user()->role === 'farmer') {
            return back()->with('error', 'Farmers cannot delete rice varieties.');
        }

        $variety = RiceVariety::findOrFail($id);

        log_activity('deleted', 'Rice variety deleted', $variety, [
            'name'           => $variety->name,
            'classification' => $variety->classification,
        ]);

        $variety->delete();

        // Bust the cache so future lookups reflect the change
        $this->forgetTrainedVarietiesCache();

        return redirect()->route('admin.rice-varieties.index')
            ->with('success', 'Rice variety deleted successfully!');
    }

    public function getYield($id, Request $request)
    {
        try {
            $variety = RiceVariety::findOrFail($id);
            $method  = $request->query('method', 'Transplanted');
            $yield   = $variety->getYieldForMethod($method);
            return response()->json([
                'success' => true,
                'avg'     => $yield->avg,
                'max'     => $yield->max,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Failed to fetch yield data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function details($id)
    {
        $variety = RiceVariety::findOrFail($id);
        return view('admin.rice-varieties.partials.detail', compact('variety'));
    }

    // ═══════════════════════════════════════════════════════════
    // ML MODEL HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Read the one-hot variety columns the ML model was trained on.
     *
     * Source priority:
     *   1. Hardcoded config (fastest, zero I/O)
     *   2. Render ML service over HTTP (cached 6h)
     *   3. Local feature_legend.json (dev / XAMPP)
     *   4. Empty array (everything shows as fallback)
     */
    private function getTrainedVarieties(): array
    {
        $hardcoded = config('santiago.ml_trained_varieties', []);
        if (!empty($hardcoded)) {
            return $hardcoded;
        }

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
     * Drop the trained-varieties cache so the next call re-fetches.
     * Called after store() and destroy() so the new/deleted variety
     * is reflected immediately in the "In ML Model / Fallback Mode" badge.
     */
    private function forgetTrainedVarietiesCache(): void
    {
        Cache::forget('ml.trained_varieties');
    }

    /**
     * Query the Render ML service for its feature legend.
     * Returns [] on any failure so the caller can fall back.
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
                \Log::warning('ML service returned non-OK', [
                    'url'    => $base . $endpoint,
                    'status' => $response->status(),
                    'body'   => substr((string) $response->body(), 0, 500),
                ]);
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
            \Log::warning('ML service fetch failed', [
                'url'   => $base . $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Given a legend (list or map of feature names), pull out the
     * variety_* entries and strip the prefix. Numeric variety_* fields
     * are ignored.
     */
    private function extractVarietyNames($legend): array
    {
        if (!is_array($legend)) return [];

        $keys     = array_keys($legend);
        $isList   = $keys === range(0, count($keys) - 1);
        $features = $isList ? array_values($legend) : $keys;

        $numericVarietyFields = [
            'variety_maturity_days',
            'variety_max_yield',
            'variety_avg_yield',
        ];

        $out = [];
        foreach ($features as $feat) {
            if (!is_string($feat)) continue;
            if (!str_starts_with($feat, 'variety_')) continue;
            if (in_array($feat, $numericVarietyFields, true)) continue;
            $out[] = substr($feat, strlen('variety_'));
        }

        return array_values(array_unique($out));
    }

    /**
     * Loose match: strip spaces, underscores, dashes, and lowercase
     * so "NSIC Rc 222" and "NSIC_Rc222" both match.
     */
    private function isVarietyTrained(string $name, array $trainedVarieties): bool
    {
        $norm = fn($s) => strtolower(preg_replace('/[\s_\-]+/', '', $s));
        $needle = $norm($name);
        foreach ($trainedVarieties as $t) {
            if ($norm($t) === $needle) return true;
        }
        return false;
    }

    /**
     * Timestamp of the last model training.
     * Prefers the remote service, falls back to a local pkl file.
     */
    private function getLastTrainedDate(): string
    {
        $base = rtrim((string) config('services.ml.url', ''), '/');
        if ($base !== '') {
            try {
                $response = Http::timeout(3)
                    ->acceptJson()
                    ->get($base . '/model-info');

                if ($response->ok()) {
                    $info = $response->json();
                    $ts   = $info['trained_at'] ?? $info['last_trained'] ?? null;
                    if ($ts) {
                        return date('M d, Y g:i A', strtotime($ts));
                    }
                }
            } catch (\Throwable $e) {
                // silent — fall through to local file
            }
        }

        $path = $this->findMlFile('model_rf.pkl');
        if (!$path) return 'Not trained yet';

        return date('M d, Y g:i A', filemtime($path));
    }

    /**
     * Search common relative paths for a file in the ml-service folder.
     * Only used in dev / XAMPP. On Hostinger this returns null.
     */
    private function findMlFile(string $filename): ?string
    {
        $candidates = [
            base_path('ml-service/' . $filename),
            base_path('../ml-service/' . $filename),
            base_path('../../ml-service/' . $filename),
            'C:/xampp/install/htdocs/crops-system/ml-service/' . $filename,
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}