<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // allow these fields to be null during import/testing
            $table->string('uid')->nullable()->change();
            $table->string('asset_id')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->text('detail')->nullable()->change();
            $table->string('accessories')->nullable()->change();
            $table->string('type_id')->nullable()->change();
            $table->string('serial_no')->nullable()->change();
            $table->string('status')->nullable()->change();
            $table->string('qr_id')->nullable()->change();
            $table->string('remarks')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // revert back to non-nullable if needed
            $table->string('uid')->nullable(false)->change();
            $table->string('asset_id')->nullable(false)->change();
            $table->string('name')->nullable(false)->change();
            $table->text('detail')->nullable(false)->change();
            $table->string('accessories')->nullable(false)->change();
            $table->string('type_id')->nullable(false)->change();
            $table->string('serial_no')->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
            $table->string('qr_id')->nullable(false)->change();
            $table->string('remarks')->nullable(false)->change();
        });
    }
};
