<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('nfc_scans', function (Blueprint $table) {
            $table->id();

            // NFC / card information
            $table->string('uid');              // NFC tag UID
            $table->string('student_id')->nullable(); // optional student/staff ID
            $table->string('user_name')->nullable();  // person’s name

            // Item details
            $table->string('item_id')->nullable();
            $table->string('item_name')->nullable();

            // Status of the item
            $table->enum('status', [
                'available',
                'borrowed',
                'under_repair',
                'stolen',
                'missing_lost'
            ])->default('available');

            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void {
        Schema::dropIfExists('nfc_scans');
    }
};
