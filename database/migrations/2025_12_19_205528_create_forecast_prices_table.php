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
        Schema::create('forecast_prices', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->float('price');
            $table->float('number_of_shares');
            $table->float('profit_forward');
            $table->float('p_e_forward');
            $table->float('eps_forward');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_prices');
    }
};
