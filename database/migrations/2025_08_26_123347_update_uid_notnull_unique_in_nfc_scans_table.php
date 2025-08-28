<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            // Make uid NOT NULL and UNIQUE
            $table->string('uid', 255)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            // Revert back if needed
            $table->string('uid', 255)->nullable()->dropUnique()->change();
        });
    }
};
