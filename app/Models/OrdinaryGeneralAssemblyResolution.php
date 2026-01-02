<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdinaryGeneralAssemblyResolution extends Model
{
    public function stock(){
        return $this->belongsTo(Stock::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
