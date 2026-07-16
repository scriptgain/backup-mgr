<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Physical disks / mount points available on a Director node. Filesystem
        // repositories are placed on these so all available space can be used.
        Schema::create('storage_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('director_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mount_path');
            $table->unsignedBigInteger('total_bytes')->nullable();
            $table->unsignedBigInteger('used_bytes')->nullable();
            $table->timestamp('reported_at')->nullable(); // last df report from the agent
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_devices');
    }
};
