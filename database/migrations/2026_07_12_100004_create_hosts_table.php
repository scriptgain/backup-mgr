<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('director_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // How the director reaches this host's data.
            $table->string('connection_type')->default('agent'); // agent|ssh|sftp|ftp|rsync|s3
            $table->string('hostname')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('username')->nullable();
            $table->string('auth_type')->nullable();  // key|password|token
            $table->text('secret')->nullable();       // encrypted — password/token
            $table->text('private_key')->nullable();  // encrypted — ssh key
            $table->json('disks')->nullable();        // selected disks/paths to protect
            $table->string('os')->nullable();
            $table->string('arch')->nullable();
            $table->string('agent_version')->nullable();
            $table->string('api_key')->nullable();          // hashed — agent connector
            $table->string('enrollment_token')->nullable(); // hashed — one-time
            $table->string('status')->default('pending');   // pending|online|offline|stale
            $table->timestamp('last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosts');
    }
};
