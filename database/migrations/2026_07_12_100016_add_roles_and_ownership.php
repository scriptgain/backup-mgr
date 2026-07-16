<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email'); // admin|user
        });
        // Existing users become admins.
        DB::table('users')->update(['role' => 'admin']);

        Schema::table('directors', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('location_id')->constrained()->nullOnDelete();
        });
        // Assign existing directors to the first admin.
        $firstAdmin = DB::table('users')->orderBy('id')->value('id');
        if ($firstAdmin) {
            DB::table('directors')->whereNull('user_id')->update(['user_id' => $firstAdmin]);
        }
    }

    public function down(): void
    {
        Schema::table('directors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
