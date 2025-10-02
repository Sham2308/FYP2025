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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who submitted
            $table->string('subject');
            $table->string('category')->nullable(); // e.g. 'device', 'account', 'other'
            $table->string('priority')->default('medium'); // low|medium|high
            $table->unsignedBigInteger('item_id')->nullable(); // link to Item if relevant
            $table->text('message');
            $table->json('attachments')->nullable(); // store file paths
            $table->string('status')->default('open'); // open|in_progress|closed
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('item_id');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
