<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            // The agent expected to execute this task. Chosen when the task is
            // queued (the Director's gateway agent) rather than left open, so a
            // filesystem repo can only ever be worked by the host that holds it.
            $table->foreignId('host_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind');                        // prune|maintenance|both
            $table->string('status')->default('queued');   // queued|running|success|failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('log')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['status', 'host_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tasks');
    }
};
