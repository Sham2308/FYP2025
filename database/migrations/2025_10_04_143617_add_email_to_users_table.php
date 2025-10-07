<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change 'users' to another table if needed
        if (! Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                // add after 'name' if that column exists; adjust as you like
                $table->string('email')->unique()->after('name');
                // optional but common in Laravel
                if (! Schema::hasColumn('users', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable()->after('email');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                // drop optional column if it was created above
                if (Schema::hasColumn('users', 'email_verified_at')) {
                    $table->dropColumn('email_verified_at');
                }
                $table->dropColumn('email');
            });
        }
    }
};
