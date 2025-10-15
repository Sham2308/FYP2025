<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // If your unique index is named 'users_email_unique' (Laravel default):
            $table->dropUnique('users_email_unique');

            // Optional: keep a non-unique index for faster lookups
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove the non-unique index
            $table->dropIndex(['email']);

            // Restore uniqueness
            $table->unique('email');
        });
    }
};
