<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class AuditLogger
{
    /**
     * Record a safe audit summary without allowing logging failure to block the action.
     *
     * @param  array<string, mixed>  $meta
     */
    public function record(
        string $action,
        ?Model $subject,
        string $description,
        array $meta = [],
        ?User $actor = null,
    ): ?AuditLog {
        try {
            $authenticatedUser = auth()->user();
            $actor ??= $authenticatedUser instanceof User ? $authenticatedUser : null;

            return AuditLog::query()->create([
                'user_id' => $actor?->getKey(),
                'action' => $action,
                'description' => Str::limit($description, 255, ''),
                'subject_type' => $subject !== null ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'metadata' => $this->sanitizeMetadata($meta),
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 255, ''),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Drop sensitive values even if a future caller passes an unsafe metadata array.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $safe = [];

        foreach ($metadata as $key => $value) {
            $normalizedKey = Str::lower((string) $key);

            if (Str::contains($normalizedKey, ['password', 'token', 'secret', 'body', 'authorization', 'remember', 'session'])) {
                continue;
            }

            if (is_array($value)) {
                $value = $this->sanitizeMetadata($value);
            } elseif (! is_scalar($value) && $value !== null) {
                continue;
            }

            $safe[(string) $key] = $value;
        }

        return $safe;
    }
}
