<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            // Set when a prune expired this run's snapshot out of the repository.
            // The run row stays (it is the history of the backup) but it no
            // longer offers a restore point, so the UI must stop listing it as
            // one: without this, a repo pruned back to 7 snapshots still shows
            // all 11 runs as restorable.
            $table->timestamp('snapshot_expired_at')->nullable()->after('snapshot_id');
        });
    }

    public function down(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->dropColumn('snapshot_expired_at');
        });
    }
};
