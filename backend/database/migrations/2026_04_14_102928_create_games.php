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
        Schema::create('games', function (Blueprint $table) {  
            $table->unsignedBigInteger('id')->primary(); 
            $table->string('title', 100);
            $table->string('developer', 50)->nullable();
            $table->string('publisher', 50)->nullable();
            $table->date('release_date')->nullable();
            $table->string('description', 1000)->nullable();
            $table->string('cover_url', 250)->nullable();
            $table->FLOAT('rating', 10, 2)->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
