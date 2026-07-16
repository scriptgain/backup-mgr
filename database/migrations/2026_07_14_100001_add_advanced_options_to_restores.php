<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restores', function (Blueprint $table) {
            // Bareos-style advanced restore controls (honored by the agent).
            $table->string('overwrite')->default('overwrite')->after('target_path'); // overwrite|skip|keep_newer
            $table->boolean('restore_ownership')->default(true)->after('overwrite');
            $table->boolean('restore_permissions')->default(true)->after('restore_ownership');
            $table->boolean('strip_paths')->default(false)->after('restore_permissions'); // restore contents into target, dropping the original prefix
            $table->boolean('dry_run')->default(false)->after('strip_paths'); // verify only, write nothing
        });
    }

    public function down(): void
    {
        Schema::table('restores', function (Blueprint $table) {
            $table->dropColumn(['overwrite', 'restore_ownership', 'restore_permissions', 'strip_paths', 'dry_run']);
        });
    }
};
