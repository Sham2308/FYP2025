<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            // NFC / QR related fields
            $table->string('uid')->nullable();          // NFC UID
            $table->string('asset_id')->nullable();     // Asset ID
            $table->string('qr_id')->nullable();        // QR Code ID

            // Item details
            $table->string('name');                     // Item Name
            $table->text('detail')->nullable();         // Item Detail/Description
            $table->string('accessories')->nullable();  // Accessories included
            $table->string('type_id')->nullable();      // Category or Type reference
            $table->string('serial_no')->nullable();    // Serial Number

            // Status & remarks
            $table->enum('status', ['available', 'borrowed', 'damaged', 'lost'])->default('available');
            $table->string('remarks')->nullable();      // Extra notes

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
