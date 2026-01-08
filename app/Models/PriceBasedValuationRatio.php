<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceBasedValuationRatio extends Model
{
    protected $fillable = ['p_b', 'p_s', 'p_e', 'p_a','p_cf','p_cfc' ];

    public function stock(){
        return $this->belongsTo(Stock::class);
    }
}
