<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            // Optional direct owner. When null, the host inherits its director's
            // owner (backward-compatible). When set, this user can see and manage
            // the host even if the director belongs to someone else.
            $table->foreignId('user_id')->nullable()->after('director_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
