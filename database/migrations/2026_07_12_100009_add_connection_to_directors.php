<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directors', function (Blueprint $table) {
            $table->string('hostname')->nullable()->after('region');   // IP or DNS of the director node
            $table->unsignedInteger('port')->nullable()->after('hostname');
            $table->string('enrollment_token')->nullable()->after('api_key'); // one-time, hashed
        });
    }

    public function down(): void
    {
        Schema::table('directors', function (Blueprint $table) {
            $table->dropColumn(['hostname', 'port', 'enrollment_token']);
        });
    }
};
