<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Context;

class AuditContext
{
    private const SERVICE_WRITES_KEY = 'audit.service_writes';

    public function runFor(string $model, Closure $callback): mixed
    {
        return Context::scope(function () use ($model, $callback): mixed {
            $models = Context::get(self::SERVICE_WRITES_KEY, []);
            Context::add(self::SERVICE_WRITES_KEY, array_values(array_unique([...$models, $model])));

            return $callback();
        });
    }

    public function isServiceWrite(string $model): bool
    {
        return in_array($model, Context::get(self::SERVICE_WRITES_KEY, []), true);
    }
}
