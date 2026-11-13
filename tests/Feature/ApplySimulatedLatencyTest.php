<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplySimulatedLatency;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class ApplySimulatedLatencyTest extends TestCase
{
    public function test_valid_local_latency_header_delays_the_request_by_the_requested_amount(): void
    {
        Sleep::fake();
        $this->app['env'] = 'local';
        config([
            'app.env' => 'local',
            'app.simulate_latency' => true,
        ]);
        Route::get('/_testing/simulated-latency', static fn () => response()->json(['ok' => true]))
            ->middleware(ApplySimulatedLatency::class);

        $this->withHeader('X-Lag', '800')
            ->getJson('/_testing/simulated-latency')
            ->assertOk();

        Sleep::assertSequence([
            Sleep::for(800)->milliseconds(),
        ]);
    }

    public function test_latency_is_not_applied_when_disabled_or_outside_the_local_environment(): void
    {
        Sleep::fake();
        Route::get('/_testing/simulated-latency', static fn () => response()->json(['ok' => true]))
            ->middleware(ApplySimulatedLatency::class);

        $this->app['env'] = 'local';
        config(['app.env' => 'local', 'app.simulate_latency' => false]);
        $this->withHeader('X-Lag', '800')->getJson('/_testing/simulated-latency')->assertOk();

        $this->app['env'] = 'testing';
        config(['app.env' => 'testing', 'app.simulate_latency' => true]);
        $this->withHeader('X-Lag', '800')->getJson('/_testing/simulated-latency')->assertOk();

        Sleep::assertNeverSlept();
    }

    public function test_invalid_local_latency_header_returns_a_client_error_without_sleeping(): void
    {
        Sleep::fake();
        $this->app['env'] = 'local';
        config([
            'app.env' => 'local',
            'app.simulate_latency' => true,
        ]);
        Route::get('/_testing/simulated-latency', static fn () => response()->json(['ok' => true]))
            ->middleware(ApplySimulatedLatency::class);

        $this->withHeader('X-Lag', '8000')
            ->getJson('/_testing/simulated-latency')
            ->assertBadRequest()
            ->assertJsonPath('message', 'The X-Lag header must be 800, 2000, or 4000 milliseconds.');

        Sleep::assertNeverSlept();
    }
}
