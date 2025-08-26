<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nfc_scans', function (Blueprint $table) {
            $table->id();
            $table->string('uid');                // NFC tag UID
            $table->string('user_name')->nullable(); // Optional: user who scanned
            $table->string('item_name')->nullable(); // Optional: item scanned
            // If you want to link to items table later, uncomment:
            // $table->unsignedBigInteger('item_id')->nullable();
            // $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->timestamps();                 // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfc_scans');
    }
};
