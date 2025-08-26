<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            $table->string('asset_id')->nullable()->after('uid');
            $table->string('name')->nullable()->after('asset_id');
            $table->text('detail')->nullable()->after('name');
            $table->text('accessories')->nullable()->after('detail');
            $table->string('type_id')->nullable()->after('accessories');
            $table->string('serial_no')->nullable()->after('type_id');
            $table->string('location_id')->nullable()->after('serial_no');
            $table->date('purchase_date')->nullable()->after('status');
            $table->text('remarks')->nullable()->after('purchase_date');
        });
    }

    public function down(): void
    {
        Schema::table('nfc_scans', function (Blueprint $table) {
            $table->dropColumn([
                'asset_id',
                'name',
                'detail',
                'accessories',
                'type_id',
                'serial_no',
                'location_id',
                'purchase_date',
                'remarks',
            ]);
        });
    }
};
