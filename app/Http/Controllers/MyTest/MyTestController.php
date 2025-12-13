<?php

namespace App\Http\Controllers\MyTest;

use App\Http\Controllers\Controller;
use App\Jobs\FetchCodalData;
use App\Models\Stock;
use App\Models\CodalReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MyTestController extends Controller
{

    public function startAutoDownloadData(){
        FetchCodalData::dispatch();
        return "done" ;
    }
    public function getStockData(Request $request , string $id){
        $posts = Stock::find($id)->with('category','monthlyStockData' ,'yearlyStockData.dollarPrice')->firstOrFail();
        return $posts;
    }
}
