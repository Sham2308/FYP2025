<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            // Just change column type (FK already dropped earlier)
            $table->string('user_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });
    }
};
