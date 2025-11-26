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
            $table->decimal('price');
            $table->string('category-id');
            $table->string('tsetmc-link');
            $table->string('rahavard-link');
            $table->string('codal-link');
            $table->string('codal-link');
            $table->decimal('last-price');
            $table->decimal('p-e-ttm');
            $table->decimal('p-e-forward');
            $table->decimal('last-dp');
            $table->string('tsetmc-id');
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
