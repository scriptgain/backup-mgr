<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_job_id')->constrained('backup_jobs')->cascadeOnDelete();
            $table->string('status')->default('queued'); // queued|running|success|warn|failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('bytes_in')->nullable();
            $table->unsignedBigInteger('bytes_uploaded')->nullable();
            $table->unsignedInteger('files')->nullable();
            $table->string('snapshot_id')->nullable();
            $table->longText('log')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
