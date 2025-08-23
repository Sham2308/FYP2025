<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            $table->string('item_id')->nullable()->after('user_name');
        });
    }

    public function down(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            $table->dropColumn('item_id');
        });
    }
};
