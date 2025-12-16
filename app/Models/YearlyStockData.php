<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearlyStockData extends Model
{
    protected $fillable = ['operating_income_in_dollars_changes'  ,'leverage' ,'net_profit_and_loss_in_dollars_changes' ,'production_cost_in_dollars_changes'  ];

    public function stock(){
        return $this->belongsTo(Stock::class);
    }
    public function dollarPrice()
    {
        return $this->belongsTo(DollarPrice::class);
    }
}
