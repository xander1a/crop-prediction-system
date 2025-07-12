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
        Schema::create('prediction_results', function (Blueprint $table) {
          $table->id();

            $table->decimal('predicted_price', 10, 2); // e.g., 3623.28
            $table->decimal('confidence_score', 5, 2); // e.g., 94.27
            $table->string('model_used'); // e.g., svr_linear

            $table->timestamp('prediction_date'); // e.g., 2025-06-28T08:48:54.455323
            $table->date('target_date');           // e.g., 2025-06-28

            $table->unsignedBigInteger('commodity_id');

            $table->string('admin1'); // e.g., Kigali City
            $table->string('admin2'); // e.g., Nyarugenge
            $table->string('market'); // e.g., Kigali
            $table->string('crop_name'); // e.g., Maize

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_results');
    }
};
