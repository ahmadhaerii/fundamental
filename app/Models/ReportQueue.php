<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportQueue extends Model
{
    protected $fillable = ['status' ];
    public function stock(){
        return $this->belongsTo(Stock::class);
    }
}
