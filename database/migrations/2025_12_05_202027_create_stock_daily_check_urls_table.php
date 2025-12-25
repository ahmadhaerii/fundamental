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
        Schema::create('stock_daily_check_urls', function (Blueprint $table) {
            $table->id();
            $table->string("codal_1_month");
            $table->string("codal_3_month");
            $table->string("codal_6_month");
            $table->string("codal_9_month");
            $table->string("codal_12_month");
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_daily_check_urls');
    }
};
