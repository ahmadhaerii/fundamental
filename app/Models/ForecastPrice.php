<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastPrice extends Model
{
    public function stock(){
        return $this->belongsTo(Stock::class);
    }
}
