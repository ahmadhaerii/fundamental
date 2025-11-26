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
        Schema::create('stock-monthly-datas', function (Blueprint $table) {
            $table->id();
            $table->decimal('m1');
            $table->decimal('m2');
            $table->decimal('m3');
            $table->decimal('m4');
            $table->decimal('m5');
            $table->decimal('m6');
            $table->decimal('m7');
            $table->decimal('m8');
            $table->decimal('m9');
            $table->decimal('m10');
            $table->decimal('m11');
            $table->decimal('m12');
            $table->decimal('seals-3-monthly');
            $table->decimal('seals-6-monthly');
            $table->decimal('seals-9-monthly');
            $table->decimal('seals-12-monthly');
            $table->decimal('net-profit-3-monthly');
            $table->decimal('net-profit-6-monthly');
            $table->decimal('net-profit-9-monthly');
            $table->decimal('net-profit-12-monthly');

            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock-monthly-datas');
    }
};
