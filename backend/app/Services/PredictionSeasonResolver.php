<?php

namespace App\Services;

class PredictionSeasonResolver
{
    /**
     * Map any stored season to a model-supported one.
     * Returns null only for genuinely unknown seasons (never happens for your data).
     *
     * @return array{season:string, is_proxy:bool, original:string}|null
     */
    public static function resolve(?string $season): ?array
    {
        $original = (string) $season;
        $s = strtolower(trim($original));

        if ($s === '' || $s === 'null') {
            return ['season' => 'Wet', 'is_proxy' => true, 'original' => 'unknown'];
        }

        // Direct hits — no proxy needed
        if (str_contains($s, 'dry')) {
            return ['season' => 'Dry', 'is_proxy' => false, 'original' => $original];
        }
        if (str_contains($s, 'wet')) {
            return ['season' => 'Wet', 'is_proxy' => false, 'original' => $original];
        }

        // Off-Season → proxy to Dry.
        // Rationale: in Region 2 (Santiago City), off-season rice is almost always
        // grown under irrigation during the drier months. If your data says
        // otherwise, swap 'Dry' for 'Wet' below.
        if (str_contains($s, 'off')) {
            return ['season' => 'Dry', 'is_proxy' => true, 'original' => $original];
        }

        // Anything else → assume Dry (safer for irrigated lowland)
        return ['season' => 'Dry', 'is_proxy' => true, 'original' => $original];
    }
}