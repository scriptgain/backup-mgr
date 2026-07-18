<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            // "ingest" connection type — a receive/drop target external systems
            // (cPanel/WHM, appliances) PUSH backups into. The Director's gateway
            // agent runs a credentialed SFTP/FTP/S3 server rooted at ingest_folder;
            // a scheduled job then snapshots that folder into the chosen repository.
            //
            // Credentials reuse the existing encrypted columns:
            //   username -> SFTP/FTP user  OR  S3 access-key-id
            //   secret   -> SFTP/FTP password (encrypted)  OR  S3 secret-key
            //   port     -> the listener port on the gateway
            $table->string('ingest_protocol')->nullable()->after('ftp_accounts'); // sftp|ftp|s3
            $table->string('ingest_folder')->nullable()->after('ingest_protocol'); // drop folder on the gateway
        });
    }

    public function down(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            $table->dropColumn(['ingest_protocol', 'ingest_folder']);
        });
    }
};
