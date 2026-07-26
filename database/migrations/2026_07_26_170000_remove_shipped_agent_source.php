<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

/**
 * One-time cleanup: delete the agent source tree that older releases shipped.
 *
 * Releases up to 1.5.3 were built from a dev working tree, which swept in the
 * agent's private Go source (25 files, and ~60 MB once its bundled binaries are
 * counted). 1.6.0 ships none of it, but an update extracts over the install and
 * never deletes what a release stopped shipping, so every instance updated from
 * an earlier build still carries it.
 *
 * This runs as a migration rather than relying on UpdateService::pruneStalePaths
 * because the prune executes from the code being REPLACED: it governs the next
 * update, not the one installing it. Migrations run from the new code, so this
 * cleans the install that is applying this release.
 *
 * Nothing in an install reads this path: install-master.sh excludes `agent` when
 * staging the app, masters fetch agent binaries from the vendor endpoint into
 * public/downloads, and a running agent lives in /opt/backup.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = base_path('agent');
        if (! is_dir($path)) {
            return;
        }
        try {
            File::deleteDirectory($path);
        } catch (\Throwable $e) {
            // A cleanup must never break an update. Whatever remains is inert,
            // and UpdateService prunes it on the next update.
        }
    }

    public function down(): void
    {
        // Nothing to restore: this directory was never meant to be installed,
        // and its contents come from a separate private repository.
    }
};
