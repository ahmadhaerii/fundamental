<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyStockData extends Model
{
    public function stock(){
        return $this->belongsTo(Stock::class);
    }
}
