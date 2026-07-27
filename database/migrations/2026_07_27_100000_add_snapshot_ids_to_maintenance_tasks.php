<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_tasks', function (Blueprint $table) {
            // Snapshots named explicitly by the operator, for kind=delete. A
            // prune plans its own expiry at poll time from run history, so it
            // leaves this null.
            $table->json('snapshot_ids')->nullable()->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_tasks', function (Blueprint $table) {
            $table->dropColumn('snapshot_ids');
        });
    }
};
