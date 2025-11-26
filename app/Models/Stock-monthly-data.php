<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMonthlyData extends Model
{
    public function stock(){
        return $this->belongsTo(Stock::class);
    }
}
