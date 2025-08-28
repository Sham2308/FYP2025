<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('borrows', function (Blueprint $table) {
            $table->id();
            $table->string('uid'); // references items.uid
            $table->unsignedBigInteger('user_id'); // who borrowed
            $table->timestamp('borrowed_at')->useCurrent();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('borrows');
    }
};
