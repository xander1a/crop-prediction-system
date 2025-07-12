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
    Schema::create('trained_models', function (Blueprint $table) {
    $table->id();
    $table->foreignId('commodity_id')->nullable()->constrained('commodities');
    $table->string('model_name'); // e.g., random_forest
    $table->string('model_file'); // filename like `crop_51_random_forest.pkl`
    $table->float('accuracy')->nullable();
    $table->string('trained_by')->nullable(); // e.g., system or user ID
    $table->timestamp('trained_at')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trained_models');
    }
};
