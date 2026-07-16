<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Run;
use Illuminate\Console\Command;

class Housekeeping extends Command
{
    protected $signature = 'backup:housekeeping';

    protected $description = 'Prune old run history and audit log rows per General settings.';

    public function handle(): int
    {
        $runDays = (int) config('backup.run_history_days', 90);
        $auditDays = (int) config('backup.audit_log_days', 180);

        $runs = 0;
        if ($runDays > 0) {
            // Only prune finished runs; never touch queued/running work.
            $runs = Run::whereIn('status', ['success', 'warn', 'failed'])
                ->where('created_at', '<', now()->subDays($runDays))
                ->delete();
        }

        $audits = 0;
        if ($auditDays > 0) {
            $audits = AuditLog::where('created_at', '<', now()->subDays($auditDays))->delete();
        }

        $this->info("Housekeeping: pruned {$runs} run(s), {$audits} audit row(s).");

        return self::SUCCESS;
    }
}
