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
   Schema::create('historical_prices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('commodity_id')->constrained('commodities')->onDelete('cascade');
    $table->foreignId('market_id')->constrained('markets')->onDelete('cascade');
    $table->date('date');
    $table->decimal('price', 10, 2);
    $table->string('currency')->default('RWF');
    $table->string('pricetype')->default('Wholesale');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_prices');
    }
};
