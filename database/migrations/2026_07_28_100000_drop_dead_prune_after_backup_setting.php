<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove the General setting that looked like the global prune switch.
     *
     * It wrote the key `prune_after_backup`, which only ever pre-checked the
     * toggle on the job create form, while the setting agents actually read is
     * `prune_all_jobs` under Maintenance. Operators turned it on and nothing
     * pruned. Pruning now lives on one page, so the orphan row goes.
     *
     * Deliberately NOT promoted to `prune_all_jobs`: this setting has never
     * caused a single snapshot to be deleted, and an upgrade that silently
     * starts expiring backups is worse than one that changes nothing.
     */
    public function up(): void
    {
        DB::table('settings')->where('key', 'prune_after_backup')->delete();
    }

    public function down(): void
    {
        // The row carried no behavior; nothing to restore.
    }
};
