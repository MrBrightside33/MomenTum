<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Sleep;
use Symfony\Component\HttpFoundation\Response;

class ApplySimulatedLatency
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('local') || ! config('app.simulate_latency')) {
            return $next($request);
        }

        $requestedLag = $request->header('X-Lag');

        if ($requestedLag === null) {
            return $next($request);
        }

        $milliseconds = filter_var($requestedLag, FILTER_VALIDATE_INT);

        if (! in_array($milliseconds, [800, 2000, 4000], true)) {
            return response()->json(['message' => 'The X-Lag header must be 800, 2000, or 4000 milliseconds.'], 400);
        }

        Sleep::for($milliseconds)->milliseconds();

        return $next($request);
    }
}
