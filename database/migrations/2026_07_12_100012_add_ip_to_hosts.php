<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->after('hostname');
            // Default schedule template for jobs created on this host (no FK to
            // keep migration ordering simple; validated at the app layer).
            $table->unsignedBigInteger('default_schedule_template_id')->nullable()->after('disks');
        });
    }

    public function down(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'default_schedule_template_id']);
        });
    }
};
