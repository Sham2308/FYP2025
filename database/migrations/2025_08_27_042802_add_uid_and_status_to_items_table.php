<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Already handled manually in MySQL, nothing to do here
    }

    public function down(): void
    {
        // Leave empty to avoid dropping important columns accidentally
    }
};
