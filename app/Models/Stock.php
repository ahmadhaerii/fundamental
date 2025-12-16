<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = ['title', 'content', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function monthlyStockData()
    {
        return $this->hasMany(MonthlyStockData::class);
    }
    public function yearlyStockData()
    {
        return $this->hasMany(YearlyStockData::class);
    }
    public function stockDailyCheckUrl()
    {
        return $this->hasOne(StockDailyCheckUrl::class);
    }
    public function codalReport()
    {
        return $this->hasOne(CodalReport::class);
    }
    public function dollarPrice()
    {
        return $this->hasOneThrough(DollarPrice::class, YearlyStockData::class);
    }
    public function reportQueue()
    {
        return $this->hasMany(ReportQueue::class);
    }

}
