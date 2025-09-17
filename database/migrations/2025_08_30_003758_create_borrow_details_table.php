<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_details', function (Blueprint $table) {
            $table->id();
            $table->string('Timestamp')->nullable();
            $table->string('BorrowID')->nullable();
            $table->string('UserID')->nullable();
            $table->string('BorrowerName')->nullable();
            $table->string('UID')->nullable();
            $table->string('AssetID')->nullable();
            $table->string('Name')->nullable();
            $table->string('BorrowDate')->nullable();
            $table->string('ReturnDate')->nullable();
            $table->string('BorrowedAt')->nullable();
            $table->string('ReturnedAt')->nullable();
            $table->string('Status')->nullable();
            $table->string('Remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_details');
    }
};
