<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            $table->string('student_id')->nullable()->after('uid');
        });
    }

    public function down(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            $table->dropColumn('student_id');
        });
    }
};
