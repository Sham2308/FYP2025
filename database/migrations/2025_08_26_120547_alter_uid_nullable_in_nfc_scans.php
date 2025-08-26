<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make uid nullable (manual register won’t fail).
     */
    public function up(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            $table->string('uid')->nullable()->change();
        });
    }

    /**
     * Rollback (make uid required again).
     */
    public function down(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            $table->string('uid')->nullable(false)->change();
        });
    }
};
