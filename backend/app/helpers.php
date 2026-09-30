<?php

use App\Helpers\ActivityLogger;

if (! function_exists('log_activity')) {
    function log_activity($action, $description = null, $subject = null, $properties = [])
    {
        return ActivityLogger::log($action, $description, $subject, $properties);
    }
}

// ═══════════════════════════════════════════════════════════
// YIELD UNIT CONVERSIONS
// ═══════════════════════════════════════════════════════════
// Filipino farmers think in cavan (50 kg palay) and kilograms,
// not metric tons. These helpers convert between the DB/model
// unit (tons per hectare) and the farmer-facing unit (cavan
// per hectare).
//
// Standard: 1 cavan = 50 kg palay (DA/PhilRice).
// ═══════════════════════════════════════════════════════════

if (! function_exists('tons_to_cavan')) {
    /**
     * Convert metric tons to cavan.
     * 1 ton = 1000 kg = 20 cavan.
     */
    function tons_to_cavan(float $tons): float
    {
        return round($tons * 20, 1);
    }
}

if (! function_exists('cavan_to_tons')) {
    function cavan_to_tons(float $cavan): float
    {
        return round($cavan / 20, 4);
    }
}

if (! function_exists('t_ha_to_cavan_ha')) {
    /**
     * Tons per hectare → cavan per hectare.
     * 1 t/ha = 20 cavan/ha.
     */
    function t_ha_to_cavan_ha(float $t_ha): float
    {
        return round($t_ha * 20, 1);
    }
}

if (! function_exists('cavan_ha_to_t_ha')) {
    function cavan_ha_to_t_ha(float $cavan_ha): float
    {
        return round($cavan_ha / 20, 4);
    }
}

if (! function_exists('cavan_total')) {
    /**
     * Total cavan harvested from a given area.
     *   $t_ha = yield in tons per hectare
     *   $ha   = land area in hectares
     */
    function cavan_total(float $t_ha, float $ha): float
    {
        return round($t_ha * 20 * $ha, 0);
    }
}

if (! function_exists('kg_total')) {
    /**
     * Total kilograms harvested from a given area.
     */
    function kg_total(float $t_ha, float $ha): float
    {
        return round($t_ha * 1000 * $ha, 0);
    }
}