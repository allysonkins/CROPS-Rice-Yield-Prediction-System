<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log($action, $description = null, $subject = null, $properties = [])
    {
        $log = new ActivityLog();
        $log->user_id = auth()->id();
        $log->action = $action;
        $log->description = $description;

        if ($subject) {
            $log->subject_type = get_class($subject);
            $log->subject_id = $subject->id;
        }

        $log->properties = $properties;
        $log->ip_address = Request::ip();
        $log->user_agent = Request::userAgent();
        $log->save();

        return $log;
    }
}