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
        Schema::create('yearly_stock_datas', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->decimal('operating-income');
            $table->decimal('net-profit-and-loss');
            $table->decimal('cost-of-production-to-sales');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->foreignId('dollar-price_id')->constrained('dollar-prices')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yearly_stock_datas');
    }
};
