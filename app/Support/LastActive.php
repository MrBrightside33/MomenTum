<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class LastActive
{
    public static function label(?Carbon $lastSeen, ?Carbon $lastLogin, string $tz): string
    {
        $lastActivity = $lastSeen ?? $lastLogin;

        if ($lastActivity === null) {
            return 'Invitation sent';
        }

        $localNow = Carbon::now($tz);
        $localActivity = $lastActivity->copy()->setTimezone($tz);
        $elapsedSeconds = max(0, $localNow->getTimestamp() - $localActivity->getTimestamp());

        if ($elapsedSeconds < 120) {
            return 'Now';
        }

        $minutes = intdiv($elapsedSeconds, 60);

        if ($minutes < 60) {
            return "{$minutes} min ago";
        }

        $hours = intdiv($elapsedSeconds, 3600);

        if ($hours < 24) {
            return $hours === 1 ? '1 hour ago' : "{$hours} hours ago";
        }

        if ($localActivity->isYesterday()) {
            return 'Yesterday';
        }

        $days = intdiv($elapsedSeconds, 86400);

        if ($days < 7) {
            return "{$days} days ago";
        }

        return $localActivity->format('M j, Y');
    }
}
