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
        Schema::create('stock_fundamentals', function (Blueprint $table) {
            $table->id();
            $table->float('inventory_turnover_period'); // دوره گردش کالا
            $table->float('receivables_collection_period'); // دوره وصول مطالبات
            $table->float('profit_margin'); //حاشیه سود
            $table->float('estimated_sales_year_end'); // برآورد فروش تا انتهای سال
            $table->float('estimated_net_profit_year_end'); // برآورد سود خالص تا انتهای سال
            $table->float('market_cap'); // ارزش روز بازار
            $table->float('sales_growth_rate'); // میزان درصد رشد فروش
            $table->float('receivables_ratio'); // نسبت مطالبات
            $table->float('average_dividend_payout'); // میانگین تقسیم سود
            $table->float('p_e_f');
            $table->float('p_s_f');
            $table->float('p_b');
            $table->float('p_a');
            $table->float('p_d_f');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_fundamentals');
    }
};
