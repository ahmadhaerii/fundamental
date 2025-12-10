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
            $table->float('m1');
            $table->float('m2');
            $table->float('m3');
            $table->float('m4');
            $table->float('m5');
            $table->float('m6');
            $table->float('m7');
            $table->float('m8');
            $table->float('m9');
            $table->float('m10');
            $table->float('m11');
            $table->float('m12');
            $table->float('seals-3-monthly');
            $table->float('seals-6-monthly');
            $table->float('seals-9-monthly');
            $table->float('seals-12-monthly');
            $table->float('net-profit-3-monthly');
            $table->float('net-profit-6-monthly');
            $table->float('net-profit-9-monthly');
            $table->float('net-profit-12-monthly');

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
