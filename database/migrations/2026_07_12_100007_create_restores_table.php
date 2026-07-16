<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('host_id')->nullable()->constrained()->nullOnDelete(); // restore target
            $table->string('snapshot_id');
            $table->json('paths')->nullable();       // specific files/dirs, null = whole snapshot
            $table->string('target_path')->nullable();
            $table->string('status')->default('queued'); // queued|running|success|failed
            $table->longText('log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restores');
    }
};
