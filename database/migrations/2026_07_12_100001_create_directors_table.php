<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('region')->nullable();
            // The built-in director that runs on the Manager host itself.
            $table->boolean('is_local')->default(false);
            $table->string('status')->default('pending'); // pending|online|offline
            // Credential the director node uses to talk to the Manager (hashed).
            $table->string('api_key')->nullable();
            $table->string('version')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directors');
    }
};
