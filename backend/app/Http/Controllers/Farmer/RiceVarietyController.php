<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\RiceVariety;
use Illuminate\Http\Request;

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

    private function getTrainedVarieties(): array
    {
        $path = $this->findMlFile('feature_legend.json');
        if (!$path) return [];

        $legend = json_decode(file_get_contents($path), true) ?? [];

        $numericVarietyFields = [
            'variety_maturity_days',
            'variety_max_yield',
            'variety_avg_yield',
        ];

        $trained = [];
        foreach (array_keys($legend) as $feat) {
            if (str_starts_with($feat, 'variety_')
                && !in_array($feat, $numericVarietyFields, true)) {
                $trained[] = substr($feat, strlen('variety_'));
            }
        }

        return $trained;
    }

    private function getLastTrainedDate(): string
    {
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