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
            $table->string('name');
            $table->string('developer')->nullable();
            $table->string('publisher')->nullable();
            $table->date('release_date')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_url')->nullable();
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
