<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class RecommendationCache
{
    /** Cache lifetime in hours. */
    public const TTL_HOURS = 24;

    /** Bump this when you change the recommendation algorithm. */
    public const VERSION = 1;

    /**
     * Build the cache key for a farm + season combination.
     */
    public static function key(int $farmId, string $season): string
    {
        return sprintf('crops.farm.%d.recs.%s.v%d', $farmId, str_replace(' ', '_', $season), self::VERSION);
    }

    /**
     * Wipe recommendation caches for a farm (both seasons, just in case).
     */
    public static function forget(int $farmId): void
    {
        foreach (['Dry Season', 'Wet Season', 'Dry', 'Wet'] as $season) {
            Cache::forget(self::key($farmId, $season));
        }
    }

    /**
     * Wipe every farm's recommendation cache.
     * Used when the rice variety catalog changes globally.
     */
    public static function forgetAll(): void
    {
        // Since we can't wildcard-forget on all drivers, use a version bump.
        Cache::forever('crops.recs.global_version', (int) (Cache::get('crops.recs.global_version', 0) + 1));
    }

    public static function globalVersion(): int
    {
        return (int) Cache::get('crops.recs.global_version', 0);
    }
}