<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('director_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('source_host_id')->constrained('hosts')->cascadeOnDelete();
            $table->string('source_path');
            $table->json('targets');            // [{ "host_id": 1, "path": "/var/www" }, ...]
            $table->boolean('delete_extra')->default(false); // mirror: remove files on targets not on main
            $table->unsignedInteger('interval_minutes')->default(15);
            $table->boolean('enabled')->default(true);
            $table->string('status')->default('idle'); // idle|running|success|failed
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_folders');
    }
};
