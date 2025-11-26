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
            $table->integer('stock_id');
            $table->integer('year');
            $table->integer('operating-income');
            $table->integer('net-profit-and-loss');
            $table->integer('cost-of-production-to-sales');
            $table->integer('dollar-price-id');
            $table->integer('');
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
