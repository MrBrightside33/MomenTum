<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserPreferences
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $request->user()?->settings['timezone'] ?? config('app.timezone');

        if (! in_array($timezone, array_keys(config('settings.timezones')), true)) {
            return $next($request);
        }

        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set($timezone);

        try {
            return $next($request);
        } finally {
            date_default_timezone_set($previousTimezone);
        }
    }
}
