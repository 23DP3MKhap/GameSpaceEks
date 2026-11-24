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
        Schema::create('user_games', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['playing', 'completed', 'planned', 'dropped'])->default('planned');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('note', 250)->nullable();
            $table->unsignedInteger('hours_played')->nullable();
            $table->unsignedTinyInteger('completion_percent')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamp('added_at')->useCurrent();

            $table->primary(['user_id', 'game_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_games');
    }
};
