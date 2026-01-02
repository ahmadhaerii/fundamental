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
        Schema::create('ordinary_general_assembly_resolutions', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->float('net_profit_loss_per_share');
            $table->float('dividend_per_share');
            $table->float('dp');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordinary_general_assembly_resolutions');
    }
};
