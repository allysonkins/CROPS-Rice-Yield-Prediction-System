<?php

use App\Helpers\ActivityLogger;

if (! function_exists('log_activity')) {
    function log_activity($action, $description = null, $subject = null, $properties = [])
    {
        return ActivityLogger::log($action, $description, $subject, $properties);
    }
}