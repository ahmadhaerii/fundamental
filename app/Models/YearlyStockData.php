<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearlyStockData extends Model
{
    public function stock(){
        return $this->belongsTo(Stock::class);
    }
    public function dollarPrice()
    {
        return $this->belongsTo(DollarPrice::class);
    }
}
