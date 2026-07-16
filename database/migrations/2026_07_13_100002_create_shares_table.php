<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Host that physically holds the files. Null = the Manager itself.
            // Non-manager hosts replicate their folder to the Manager for serving.
            $table->foreignId('host_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();          // public URL segment: /s/{slug}
            $table->string('token', 40)->unique();      // unguessable link: /d/{token}
            $table->string('path');                     // folder on disk (under shares base)
            $table->string('visibility')->default('private'); // public|private
            $table->string('password')->nullable();     // hashed; optional gate
            $table->boolean('allow_uploads')->default(false);
            $table->boolean('allow_listing')->default(true);   // directory index
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('downloads')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
