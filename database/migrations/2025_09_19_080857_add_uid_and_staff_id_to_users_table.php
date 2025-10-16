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
        Schema::table('users', function (Blueprint $table) {
            // Add UID (unique) and Staff ID
            $table->string('uid')->nullable()->unique()->after('id');
            $table->string('staff_id')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // First drop unique index on uid
            $table->dropUnique(['uid']);
            // Then drop the columns
            $table->dropColumn(['uid', 'staff_id']);
        });
    }
};
