<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('director_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('backend')->default('s3'); // s3|filesystem|sftp
            $table->json('config')->nullable();        // endpoint, region, bucket, prefix, path
            $table->string('access_key_id')->nullable();
            $table->text('secret_access_key')->nullable(); // encrypted (model cast)
            $table->text('password')->nullable();          // encrypted — kopia repo password
            $table->string('compression')->default('zstd');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
