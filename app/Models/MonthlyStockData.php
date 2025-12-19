<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyStockData extends Model
{
    protected $fillable = ['m1', 'm2', 'm3', 'm4', 'm5', 'm6', 'm7', 'm8', 'm9', 'm10', 'm11', 'm12',
        'operating_income_3_monthly', 'operating_income_6_monthly', 'operating_income_9_monthly', 'operating_income_12_monthly'
        , 'net_profit_and_loss_3_monthly', 'net_profit_and_loss_6_monthly', 'net_profit_and_loss_9_monthly',
        'net_profit_and_loss_12_monthly', 'production_cost_3_monthly', 'production_cost_6_monthly',
        'production_cost_9_monthly', 'production_cost_12_monthly' ];

    public function stock(){
        return $this->belongsTo(Stock::class);
    }
}
