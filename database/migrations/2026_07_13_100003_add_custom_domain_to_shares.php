<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            // A user's own domain (e.g. cdn.example.com) pointed at the Manager.
            $table->string('custom_domain')->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn('custom_domain');
        });
    }
};
