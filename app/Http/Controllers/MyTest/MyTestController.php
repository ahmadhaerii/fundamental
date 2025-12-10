<?php

namespace App\Http\Controllers\MyTest;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\CodalReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MyTestController extends Controller
{

    public function getStockData(Request $request , string $id){
        $posts = Stock::find($id)->with('category','monthlyStockData' ,'yearlyStockData.dollarPrice')->firstOrFail();
        return $posts;
    }
    /**
     * Update the user's profile information.
     */
    public function getStock(Request $request , string $id)
    {

//        $response = Http::get('https://cdn.tsetmc.com/api/Instrument/GetInstrumentInfo/778253364357513');
//        $response = Http::get('https://cdn.tsetmc.com/api/ClosingPrice/GetClosingPriceInfo/778253364357513');
        $stock = Stock::find($id)->with('stockDailyCheckUrl')->firstOrFail();
        if ($stock == null) {
            return "سهم پیدا نشد";
        }

        $out = $this->getCodal_1_monthData($stock);

//        $response = Http::withHeaders([
//            'Accept' => 'application/json',
//        ])->get($stock->stockDailyCheckUrl->codal_1_month);
//
//        $codalData = $response->json();
//        $this->getCodal_3_monthData($codalData , $stock);
//        $this->getCodal_6_monthData($codalData , $stock);
//        $this->getCodal_9_monthData($codalData , $stock);
//        $this->getCodal_12_monthData($codalData , $stock);

        return $out;

    }

    private function getCodal_1_monthData( Stock $stock  ){
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get($stock->stockDailyCheckUrl->codal_1_month);

        $codalData = $response->json();
        for ($i = 1; $i <= $codalData['Page']; $i++) {
            if ($i> 1){
                $codal1MonthUrl = str_replace("PageNumber=1", "PageNumber=".$i, $stock->stockDailyCheckUrl->codal_1_month);

                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->get($codal1MonthUrl);

                $codalData = $response->json();
            }


            foreach ($codalData['Letters'] as $letter) {
                $codalReport = CodalReport::where('tracing_no', $letter["TracingNo"])->get()->first();
                if ($codalReport == null) {
                    error_log('get data for ' . $letter["TracingNo"]);

                    $url = 'https://www.codal.ir' . $letter["Url"];


                    $netProfitLossData = Http::withHeaders([
                        'Accept' => 'application/json',
                    ])->get($url);
                    $html = $netProfitLossData->body();

                    preg_match('/var\s+datasource\s*=\s*(\{.*?\});/s', $html, $matches);
                    if (!empty($matches[1])) {
                        $datasourceRaw = $matches[1];
                        $datasourceRaw = trim($datasourceRaw, ";\n\r ");
                        $datasourceArray = json_decode($datasourceRaw, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $datasourceRaw = preg_replace('/,\s*]/', ']', $datasourceRaw); // حذف کاما اضافه
                            $datasourceArray = json_decode($datasourceRaw, true);
                        }


                        if (is_null($datasourceArray) || empty($datasourceArray)){
                            return "hasProblem";
                        }

                        $codalReport = new CodalReport();
                        $codalReport->report_period = 1 ;
                        $codalReport->tracing_no = $datasourceArray['tracingNo'];

                        $codalReport->url = $url;

                        $codalReport->year_end_to_date = $datasourceArray['yearEndToDate'];
                        $codalReport->period_end_to_date = $datasourceArray['periodEndToDate'];
                        $codalReport->trade_and_other_receivables = 0;
                        $codalReport->number_of_shares = 0;
                        $codalReport->total_equity = 0;
                        $codalReport->total_equity = 0;
                        $codalReport->net_profit_loss = 0;
                        $codalReport->ending_period_finished_goods_inventory = 0;
                        $codalReport->first_period_finished_goods_inventory = 0;
                        $codalReport->cost_of_goods_sold = 0;
                        $codalReport->stock_id = $stock->id;
                        $sheet = $datasourceArray['sheets'][0] ;

                        if ($sheet['code'] !== 1000000){
                            return "hasProblem1=>" . $sheet['code'];
                        }
                        $tables = $sheet["tables"];
                        $key = array_search(1197,  array_column($tables, 'code'));
                        $cells = $datasourceArray['sheets'][0]["tables"][$key]["cells"];

                        foreach ($cells as $cell) {
                            // "type":6,
                            // "period":6

                            if ($cell['rowCode'] === 16  &&  $cell['columnCode'] === 17   ){
                                $codalReport->operating_income = $cell['value'];
                                $codalReport->save();
                            }
                        }

                    }else {
                        return "hasProblem";
                    }

                }
            }
        }

        return  "DONE" ;
    }

    private function getCodal_3_monthData(array $codalData , Stock $stock){
        foreach ($codalData['Letters'] as $letter) {
            $codalReport = CodalReport::where('tracing_no', $letter["TracingNo"])->get()->first();
            if ($codalReport == null) {
                error_log('get data for ' . $letter["TracingNo"]);

                $url = 'https://www.codal.ir' . $letter["Url"];
                $codalReport1 = new CodalReport();
                $codalReport1->report_period = 3 ;
                $codalReport1 = $this->getCodalReportForSheetOne($codalReport1 , $url);

                if ($codalReport1 !== 'hasProblem') {

                    $codalReport1 = $this->getCodalReportForSheetZero($codalReport1 , $url);

                    if ($codalReport1 !== 'hasProblem') {

                        $codalReport1 = $this->getCodalReportForSheet20($codalReport1 , $url);
                        if ($codalReport1 !== 'hasProblem') {
                            $codalReport1->stock_id = $stock->id;
                            $codalReport1->save();
                        }else{
//                  todo   save data for this address
                        }
                    }else{
//                  todo   save data for this address
                    }
                }else{
//                  todo   save data for this address
                }
            }


        }
    }


    private function getCodal_6_monthData(array $codalData , Stock $stock){
        foreach ($codalData['Letters'] as $letter) {
            $codalReport = CodalReport::where('tracing_no', $letter["TracingNo"])->get()->first();
            if ($codalReport == null) {
                error_log('get data for ' . $letter["TracingNo"]);

                $url = 'https://www.codal.ir' . $letter["Url"];
                $codalReport1 = new CodalReport();
                $codalReport1->report_period = 6 ;
                $codalReport1 = $this->getCodalReportForSheetOne($codalReport1 , $url);

                if ($codalReport1 !== 'hasProblem') {

                    $codalReport1 = $this->getCodalReportForSheetZero($codalReport1 , $url);

                    if ($codalReport1 !== 'hasProblem') {

                        $codalReport1 = $this->getCodalReportForSheet20($codalReport1 , $url);
                        if ($codalReport1 !== 'hasProblem') {
                            $codalReport1->stock_id = $stock->id;
                            $codalReport1->save();
                        }else{
//                  todo   save data for this address
                        }
                    }else{
//                  todo   save data for this address
                    }
                }else{
//                  todo   save data for this address
                }
            }


        }
    }

    private function getCodal_9_monthData(array $codalData , Stock $stock){
        foreach ($codalData['Letters'] as $letter) {
            $codalReport = CodalReport::where('tracing_no', $letter["TracingNo"])->get()->first();
            if ($codalReport == null) {
                error_log('get data for ' . $letter["TracingNo"]);

                $url = 'https://www.codal.ir' . $letter["Url"];
                $codalReport1 = new CodalReport();
                $codalReport1->report_period = 9 ;
                $codalReport1 = $this->getCodalReportForSheetOne($codalReport1 , $url);

                if ($codalReport1 !== 'hasProblem') {

                    $codalReport1 = $this->getCodalReportForSheetZero($codalReport1 , $url);

                    if ($codalReport1 !== 'hasProblem') {

                        $codalReport1 = $this->getCodalReportForSheet20($codalReport1 , $url);
                        if ($codalReport1 !== 'hasProblem') {
                            $codalReport1->stock_id = $stock->id;
                            $codalReport1->save();
                        }else{
//                  todo   save data for this address
                        }
                    }else{
//                  todo   save data for this address
                    }
                }else{
//                  todo   save data for this address
                }
            }


        }
    }

    private function getCodal_12_monthData( string $codalData , Stock $stock){
        foreach ($codalData['Letters'] as $letter) {
            $codalReport = CodalReport::where('tracing_no', $letter["TracingNo"])->get()->first();
            if ($codalReport == null) {
                error_log('get data for ' . $letter["TracingNo"]);

                $url = 'https://www.codal.ir' . $letter["Url"];
                $codalReport1 = new CodalReport();
                $codalReport1->codal_reports = 12 ;
                $codalReport1 = $this->getCodalReportForSheetOne($codalReport1 , $url);

                if ($codalReport1 !== 'hasProblem') {

                    $codalReport1 = $this->getCodalReportForSheetZero($codalReport1 , $url);

                    if ($codalReport1 !== 'hasProblem') {

                        $codalReport1 = $this->getCodalReportForSheet20($codalReport1 , $url);
                        if ($codalReport1 !== 'hasProblem') {
                            $codalReport1->stock_id = $stock->id;
                            $codalReport1->save();
                        }else{
//                  todo   save data for this address
                        }
                    }else{
//                  todo   save data for this address
                    }
                }else{
//                  todo   save data for this address
                }
            }


        }
    }

    // دریافت اطلاعات از بخش ....
    // آی دی 223 مربوط به جدول .... است
    // آی دی 31 مربوط به .... است
    // آی دی 32 مربوط به .... است
    // آی دی 33 مربوط به .... است
    private function getCodalReportForSheetOne(CodalReport $codalReport , string $url)
    {
        $netProfitLossData = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get($url . '&sheetId=1');
        $html = $netProfitLossData->body();
        preg_match('/var\s+datasource\s*=\s*(\{.*?\});/s', $html, $matches);
        if (!empty($matches[1])) {
            $datasourceRaw = $matches[1];
            $datasourceRaw = trim($datasourceRaw, ";\n\r ");
            $datasourceArray = json_decode($datasourceRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $datasourceRaw = preg_replace('/,\s*]/', ']', $datasourceRaw); // حذف کاما اضافه
                $datasourceArray = json_decode($datasourceRaw, true);
            }


            if (is_null($datasourceArray) || empty($datasourceArray)){
                return "hasProblem";
            }

                $codalReport->tracing_no = $datasourceArray['tracingNo'];
                $codalReport->url = $url;

                $codalReport->year_end_to_date = $datasourceArray['yearEndToDate'];
                $codalReport->period_end_to_date = $datasourceArray['periodEndToDate'];

                $sheet = $datasourceArray['sheets'][0] ;

                 if ($sheet['code'] !== 1){
                     return "hasProblem";
                 }
                $cells = $sheet["tables"][0]["cells"];


                foreach ($cells as $cell) {
                    // "type":6,
                    // "period":6

                    if ($cell['rowCode'] === 3  &&  $cell['columnCode'] === 2   ){

                        $codalReport->operating_income = $cell['value'];

                    }

                    if ($cell['rowCode'] === 17  &&  $cell['columnCode'] === 2  ){

                        $codalReport->net_profit_loss = $cell['value'];

                    }

                    if ($cell['rowCode'] === 42  &&  $cell['columnCode'] === 2    ){

                        $codalReport->number_of_shares = $cell['value'];

                    }
                }

                return $codalReport ;


        }else {
            return "hasProblem";
        }

    }

    // دریافت اطلاعات از بخش ....
    // آی دی 223 مربوط به جدول .... است
    // آی دی 31 مربوط به .... است
    // آی دی 32 مربوط به .... است
    // آی دی 33 مربوط به .... است
    private function getCodalReportForSheetZero(CodalReport $codalReport , string $url)
    {
        $netProfitLossData = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get($url . '&sheetId=0');
        $html = $netProfitLossData->body();

        preg_match('/var\s+datasource\s*=\s*(\{.*?\});/s', $html, $matches);
        if (!empty($matches[1])) {
            $datasourceRaw = $matches[1];
            $datasourceRaw = trim($datasourceRaw, ";\n\r ");
            $datasourceArray = json_decode($datasourceRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $datasourceRaw = preg_replace('/,\s*]/', ']', $datasourceRaw); // حذف کاما اضافه
                $datasourceArray = json_decode($datasourceRaw, true);
            }

            if (is_null($datasourceArray) || empty($datasourceArray)){
                return "hasProblem";
            }
            $sheet = $datasourceArray['sheets'][0] ;
            if ($sheet['code'] !== 0 || $sheet['sequence'] !== 4 ){
                return "hasProblem";
            }

            $cells = $sheet["tables"][0]["cells"];
            foreach ($cells as $cell) {
                // "type":6,
                // "period":6
                if ($cell['rowCode'] === 44  &&  $cell['columnCode'] === 2    ){

                        $codalReport->trade_and_other_receivables =  $cell['value'];

                }
                if ($cell['rowCode'] === 39  &&  $cell['columnCode'] === 2    ){
                        $codalReport->total_equity = $cell['value'];
                }

            }
            return $codalReport ;

        }else{
            return "hasProblem";
        }

    }

    // دریافت اطلاعات از بخش ....
    // آی دی 223 مربوط به جدول .... است
    // آی دی 31 مربوط به .... است
    // آی دی 32 مربوط به .... است
    // آی دی 33 مربوط به .... است
    private function getCodalReportForSheet20(CodalReport $codalReport , string $url)
    {
        $netProfitLossData = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get($url . '&sheetId=20');
        $html = $netProfitLossData->body();
        preg_match('/var\s+datasource\s*=\s*(\{.*?\});/s', $html, $matches);
        if (!empty($matches[1])) {
            $datasourceRaw = $matches[1];
            $datasourceRaw = trim($datasourceRaw, ";\n\r ");
            $datasourceArray = json_decode($datasourceRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $datasourceRaw = preg_replace('/,\s*]/', ']', $datasourceRaw); // حذف کاما اضافه
                $datasourceArray = json_decode($datasourceRaw, true);
            }
            if (is_null($datasourceArray) || empty($datasourceArray)){
                return "hasProblem";
            }

            $sheet = $datasourceArray['sheets'][0] ;

            if ($sheet['code'] !== 20){
                return "hasProblem";
            }

            $key = array_search(223,  array_column($datasourceArray['sheets'][0]["tables"], 'code'));
            $cells = $datasourceArray['sheets'][0]["tables"][$key]["cells"];


            foreach ($cells as $cell) {
                // "type":6,
                // "period":6

                if ($cell['rowCode'] === 31 &&  $cell['columnCode'] === 2  ){
                   $codalReport->first_period_finished_goods_inventory = $cell['value'];
                }

                if ($cell['rowCode'] === 32  &&  $cell['columnCode'] === 2  ){
                   $codalReport->ending_period_finished_goods_inventory = $cell['value'];
               }

                if ($cell['rowCode'] === 33  &&  $cell['columnCode'] === 2  ){
                    $codalReport->cost_of_goods_sold = $cell['value'];

                }

            }

            return $codalReport ;

        }else{
            return "hasProblem";
        }

    }
}
