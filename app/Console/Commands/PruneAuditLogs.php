<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('audit:prune {--days=180}')]
#[Description('Delete audit log rows older than the configured retention period')]
class PruneAuditLogs extends Command
{
    public function handle(): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);

        if ($days === false || $days < 1) {
            $this->error('The --days option must be a positive whole number.');

            return self::FAILURE;
        }

        $deleted = DB::table('audit_logs')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Pruned {$deleted} audit log row(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
