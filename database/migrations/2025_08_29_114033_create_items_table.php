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
        Schema::create('items', function (Blueprint $table) {
            $table->string('asset_id')->primary();   // Asset ID is unique identifier
            $table->string('uid')->nullable();
            $table->string('qr_id')->nullable();
            $table->string('name');
            $table->text('detail')->nullable();
            $table->string('accessories')->nullable();
            $table->string('type_id')->nullable();
            $table->string('serial_no')->nullable();

            $table->enum('status', [
                'available',
                'borrowed',
                'retire',
                'under repair',
                'stolen',
                'missing/lost'
            ])->default('available');

            $table->date('purchase_date')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
