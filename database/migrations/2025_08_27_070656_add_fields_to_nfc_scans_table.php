<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            if (!Schema::hasColumn('nfc_scans', 'status')) {
                $table->enum('status', [
                    'available',
                    'borrowed',
                    'under_repair',
                    'stolen',
                    'missing_lost'
                ])->default('available')->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            if (Schema::hasColumn('nfc_scans', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
