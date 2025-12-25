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
        Schema::create('codal_reports', function (Blueprint $table) {
            $table->id();
            $table->string("tracing_no");
            $table->string("url");
            $table->string("period_end_to_date");
            $table->string("year_end_to_date");
            $table->string("trade_and_other_receivables");// دريافتني‌هاي تجاري و ساير دريافتني‌ها
            $table->string("number_of_shares"); // تعداد سهام
            $table->string("total_equity");//جمع حقوق مالکانه
            $table->string("net_profit_loss"); // سود(زيان) خالص
            $table->string("operating_income"); // درآمدهاي عملياتي
            $table->string("ending_period_finished_goods_inventory"); // موجودي كالاي ساخته شده پايان دوره
            $table->string("first_period_finished_goods_inventory"); // موجودي كالاي ساخته شده اول دوره
            $table->string("cost_of_goods_sold"); // بهاي تمام شده كالاي فروش رفته


            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codal_reports');
    }
};
