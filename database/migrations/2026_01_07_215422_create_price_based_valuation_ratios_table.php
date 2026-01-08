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
        Schema::create('price_based_valuation_ratios', function (Blueprint $table) {
            $table->id();
            $table->float('p_b');
            $table->float('p_s');
            $table->float('p_e');
            $table->float('p_a');
            $table->float('p_cf');
            $table->float('p_cfc');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_based_valuation_ratios');
    }
};
