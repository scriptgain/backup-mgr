<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('keep_latest')->default(0);
            $table->unsignedInteger('keep_hourly')->default(0);
            $table->unsignedInteger('keep_daily')->default(0);
            $table->unsignedInteger('keep_weekly')->default(0);
            $table->unsignedInteger('keep_monthly')->default(0);
            $table->unsignedInteger('keep_annual')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_policies');
    }
};
