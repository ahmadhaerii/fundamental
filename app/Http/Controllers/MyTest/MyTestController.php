<?php

namespace App\Http\Controllers\MyTest;

use App\Http\Controllers\Controller;
use App\Jobs\FetchCodalData;
use App\Jobs\FetchReportsData;
use App\Models\Stock;
use App\Models\CodalReport;
use App\Services\CalculateForecastPriceData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MyTestController extends Controller
{
    public function __construct(
        protected CalculateForecastPriceData $calculateForecastPriceData,
    ) {}

    public function startAutoDownloadData(){
        FetchCodalData::dispatch();
        FetchReportsData::dispatch();
        return "done" ;
    }
    public function startAutoDownloadCodal(){
        FetchCodalData::dispatch();
        return "done" ;
    }
    public function getStockData(Request $request , string $id){
        $stock = Stock::find($id)->with('category','monthlyStockData' ,'yearlyStockData.dollarPrice','lastForecastPrice')->firstOrFail();
        $stock->forecast_monthly_stock_data =   $this->calculateForecastPriceData->calculate($stock->id);
        return $stock;
    }
}
