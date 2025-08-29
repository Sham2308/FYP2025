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
        Schema::create('borrows', function (Blueprint $table) {
            $table->id();
            $table->string('uid');                          // Links to NFC tag
            $table->string('user_id');                      // Student/Staff ID
            $table->string('borrower_name')->nullable();    // Borrower Name
            $table->date('borrow_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('return_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('borrowed_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->timestamps();

            // Optional: relation to items table (if UID exists in items)
            // $table->foreign('uid')->references('uid')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrows');
    }
};
