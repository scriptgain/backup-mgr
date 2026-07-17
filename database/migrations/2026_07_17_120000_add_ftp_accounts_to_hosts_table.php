<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            // Encrypted JSON list of FTP accounts for a "multiftp" host: each
            // {label, host, port, username, password, path}. Backed up together,
            // one folder per account, in a single snapshot.
            $table->text('ftp_accounts')->nullable()->after('private_key');
        });
    }

    public function down(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            $table->dropColumn('ftp_accounts');
        });
    }
};
