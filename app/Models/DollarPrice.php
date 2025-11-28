<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DollarPrice extends Model
{
    public function yearlyStockData()
    {
        return $this->hasMany(YearlyStockData::class);
    }
}
