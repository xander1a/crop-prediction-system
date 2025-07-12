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
        Schema::create('price_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained()->onDelete('cascade');
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->decimal('predicted_price', 10, 2);
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->date('prediction_date');
            $table->date('target_date');
            $table->string('model_used');
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->timestamps();       
            $table->index(['crop_id', 'region_id', 'target_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_predictions');
    }
};
