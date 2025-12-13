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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('price');
            $table->string('tsetmc-link');
            $table->string('rahavard-link');
            $table->string('codal-link');
            $table->float('last-price');
            $table->float('p-e-ttm');
            $table->float('p-e-forward');
            $table->float('last-dp');
            $table->string('tsetmc-id');
            $table->string('update_time');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
