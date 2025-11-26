<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }


}
