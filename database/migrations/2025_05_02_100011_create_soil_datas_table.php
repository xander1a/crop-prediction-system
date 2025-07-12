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
        Schema::create('soil_datas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->decimal('ph_level', 3, 1);
            $table->decimal('nitrogen_content', 5, 2);
            $table->decimal('phosphorus_content', 5, 2);
            $table->decimal('potassium_content', 5, 2);
            $table->date('date_recorded');
            $table->timestamps();
            
            $table->index(['region_id', 'date_recorded']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soil_datas');
    }
};
