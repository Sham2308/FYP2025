<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // make user_id optional
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // guest fields
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_email')->nullable()->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // rollback guest fields
            $table->dropColumn(['guest_name', 'guest_email']);

            // (attempt) make user_id required again
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};