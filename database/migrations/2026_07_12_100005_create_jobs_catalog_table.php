<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named backup_jobs to avoid colliding with Laravel's queue "jobs" table.
        Schema::create('backup_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repository_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('retention_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('files');      // files|mysql|postgres|composite
            $table->string('connector')->default('agent'); // usually mirrors host connection
            $table->json('source')->nullable();            // paths, excludes, db config
            $table->string('schedule_cron')->nullable();   // backup schedule; null = manual only
            $table->boolean('enabled')->default(true);
            // Pruning / maintenance: apply the retention policy + kopia maintenance
            // on its own cadence, or right after each backup.
            $table->boolean('prune_after_backup')->default(true);
            $table->string('prune_schedule_cron')->nullable(); // null = only prune_after_backup
            $table->text('pre_hook')->nullable();
            $table->text('post_hook')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_jobs');
    }
};
