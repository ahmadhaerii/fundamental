<?php

namespace App\Jobs;

use App\Models\CodalReport;
use App\Models\DollarPrice;
use App\Models\MonthlyStockData;
use App\Models\ReportQueue;
use App\Models\Stock;
use App\Models\YearlyStockData;
use App\Services\CalculateForecastPriceData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchReportsData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, \Illuminate\Bus\Queueable, SerializesModels;
    protected $calculateForecastPriceData;


    /**
     * Create a new job instance.
     */
    public function __construct(

    ) {}

    public function handle(CalculateForecastPriceData $calculateForecastPriceData)
    {
        $this->calculateForecastPriceData = $calculateForecastPriceData;


        $reportQueue = ReportQueue::where('status', 'pending')->with('stock')->orderBy('tracing_no', 'asc')->get()->first();
        if ($reportQueue != null) {
            error_log('getdata for   ' .$reportQueue->tracing_no . "  " . $reportQueue->report_type);

            if ($reportQueue->report_type == 'm1') {
                $this->getCodal_1_monthData($reportQueue);
            }elseif ($reportQueue->report_type == 'm3') {
                $this->getCodal_3_monthData($reportQueue);
            }elseif($reportQueue->report_type == 'm6') {
               $this->getCodal_6_monthData($reportQueue);
           }elseif($reportQueue->report_type == 'm9') {
               $this->getCodal_9_monthData($reportQueue);
            }elseif($reportQueue->report_type == 'm12') {
               $this->getCodal_12_monthData($reportQueue);
            }

        }

          self::dispatch()->delay(now()->addSeconds(20));
    }

    private function getCodal_1_monthData( ReportQueue $reportQueue  ){

                $codalReport = CodalReport::where('tracing_no', $reportQueue->tracing_no)->get()->first();
                if ($codalReport == null) {
                    $htmlData = Http::withHeaders([
                        'Accept' => 'application/json',
                    ])->get($reportQueue->url);
                    $html = $htmlData->body();
                    error_log(" 111111 => " );

                    preg_match('/var\s+datasource\s*=\s*(\{.*?\});/s', $html, $matches);
                    if (!empty($matches[1])) {
                        $datasourceRaw = $matches[1];
                        $datasourceRaw = trim($datasourceRaw, ";\n\r ");
                        $datasourceArray = json_decode($datasourceRaw, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $datasourceRaw = preg_replace('/,\s*]/', ']', $datasourceRaw); // حذف کاما اضافه
                            $datasourceArray = json_decode($datasourceRaw, true);
                        }

                        error_log(" 22222222 => " );

                        if (is_null($datasourceArray) || empty($datasourceArray)){
                            $reportQueue->update([
                                'status' => "failed-no-datasource",
                            ]);
                            return "hasProblem";
                        }
                        error_log(" 333 => " );

                        $codalReport = new CodalReport();
                        $codalReport->report_period = 1 ;
                        $codalReport->tracing_no = $datasourceArray['tracingNo'];
                        $codalReport->url = $reportQueue->url;
                        $codalReport->year_end_to_date = $datasourceArray['yearEndToDate'];
                        $codalReport->year = explode("/", $codalReport->year_end_to_date)[0] ;
                        $codalReport->period_end_to_date = $datasourceArray['periodEndToDate'];
                        $codalReport->trade_and_other_receivables = 0;
                        $codalReport->number_of_shares = 0;
                        $codalReport->total_equity = 0;
                        $codalReport->net_profit_loss = 0;
                        $codalReport->ending_period_finished_goods_inventory = 0;
                        $codalReport->first_period_finished_goods_inventory = 0;
                        $codalReport->cost_of_goods_sold = 0;
                        $codalReport->stock_id = $reportQueue->stock->id;
                        $sheet = $datasourceArray['sheets'][0] ;

                        if ($sheet['code'] !== 1000000){
                            $reportQueue->update([
                                'status' => "failed-no-sheet-code-1000000",
                            ]);
                            return "hasProblem1=>" . $sheet['code'];
                        }
                        error_log(" 444444444 => " );

                        $tables = $sheet["tables"];
                        $key = array_search(1197,  array_column($tables, 'code'));
                        $cells = $datasourceArray['sheets'][0]["tables"][$key]["cells"];
                        error_log(" 555555555555 => " );

                        foreach ($cells as $cell) {

                            if ($cell['rowCode'] === 16  &&  $cell['columnCode'] === 17   ){
                                $codalReport->operating_income = $cell['value'];
                                $codalReport->save();

                                error_log(" 66666666666 => " );

                                $monthlyStockData = MonthlyStockData::where('stock_id', $codalReport->stock_id)->where('year', $codalReport->year)->get()->first();
                                error_log(" monthlyStockData => " . $monthlyStockData );
                                $dbFiledName =  'm'. (int) explode("/", $codalReport->period_end_to_date)[1]  ;

                                if ($monthlyStockData !== null) {
                                    $monthlyStockData->update([
                                        $dbFiledName => $codalReport->operating_income
                                    ]);
                                }else {
                                    error_log($dbFiledName  ."   " .  $codalReport->operating_income );
                                    $this->saveMonthlyStockData($codalReport->year , $codalReport->stock_id);
                                    $monthlyStockData = MonthlyStockData::where('stock_id', $codalReport->stock_id)->where('year', $codalReport->year)->get()->first();

                                    $monthlyStockData->update([
                                        $dbFiledName => $codalReport->operating_income
                                    ]);
                                }
                                error_log(" 7777777777 $dbFiledName => " . $dbFiledName  .  "  year   " .  $codalReport->year  .    "  stock_id   "  .   $codalReport->stock_id  .  "   operating_income  "  . $codalReport->operating_income);
                                $this->calculateForecastPriceData->updateForecastPriceData($codalReport->stock_id);

                                error_log('status  success ' );
                                $reportQueue->update([
                                    'status' => "success",
                                ]);
                                return "success";
                            }
                        }
                        error_log(" 9999999999999 => " );

                        $reportQueue->update([
                            'status' => "failed-rowCode-not-found",
                        ]);
                    }else {
                        error_log(" 8888888888 => " );

                        $reportQueue->update([
                            'status' => "failed-not-found",
                        ]);
                        return "hasProblem";
                    }

                }else {
                    error_log(" 11111100000000 => " );

                    $reportQueue->update([
                        'status' => "failed-downloaded-before",
                    ]);
                }
    }

    private function saveMonthlyStockData(int  $year  , int $stockId){
        $monthlyStockData = new MonthlyStockData();
        $monthlyStockData->year = $year ;
        $monthlyStockData->stock_id = $stockId ;
        $monthlyStockData->m1 = 0 ;
        $monthlyStockData->m2 = 0 ;
        $monthlyStockData->m3 = 0 ;
        $monthlyStockData->m4 = 0 ;
        $monthlyStockData->m5 = 0 ;
        $monthlyStockData->m6 = 0 ;
        $monthlyStockData->m7 = 0 ;
        $monthlyStockData->m8 = 0 ;
        $monthlyStockData->m9 = 0 ;
        $monthlyStockData->m10 = 0 ;
        $monthlyStockData->m11 = 0 ;
        $monthlyStockData->m12 = 0 ;
        $monthlyStockData->operating_income_3_monthly = 0 ;
        $monthlyStockData->operating_income_6_monthly = 0 ;
        $monthlyStockData->operating_income_9_monthly = 0 ;
        $monthlyStockData->operating_income_12_monthly = 0 ;
        $monthlyStockData->net_profit_and_loss_3_monthly = 0 ;
        $monthlyStockData->net_profit_and_loss_6_monthly = 0 ;
        $monthlyStockData->net_profit_and_loss_9_monthly = 0 ;
        $monthlyStockData->net_profit_and_loss_12_monthly = 0 ;
        $monthlyStockData->production_cost_3_monthly = 0 ;
        $monthlyStockData->production_cost_6_monthly = 0 ;
        $monthlyStockData->production_cost_9_monthly = 0 ;
        $monthlyStockData->production_cost_12_monthly = 0 ;
        $monthlyStockData->save();
        error_log('  save data for monthly in db => ' . $year . '  ' . $stockId );

    }
    private function getCodal_3_monthData(ReportQueue $reportQueue ){
            $codalReport = CodalReport::where('tracing_no', $reportQueue->tracing_no )->get()->first();
            if ($codalReport == null) {

                $codalReport1 = new CodalReport();
                $codalReport1->report_period = 3 ;
                $codalReport1 = $this->getCodalReportForSheetOne($codalReport1 , $reportQueue->url);
                error_log(" 111111 => " );

                if ($codalReport1 !== 'hasProblem') {

                    $codalReport1 = $this->getCodalReportForSheetZero($codalReport1 , $reportQueue->url);

                    if ($codalReport1 !== 'hasProblem') {
                        error_log(" 222222 => " );

                        $codalReport1 = $this->getCodalReportForSheet20($codalReport1 , $reportQueue->url);
                        if ($codalReport1 !== 'hasProblem') {
                            $codalReport1->stock_id = $reportQueue->stock->id;
                            $codalReport1->save();
                            error_log(" 333333 => " );

                            $monthlyStockData = MonthlyStockData::where('stock_id', $codalReport1->stock_id)->where('year', $codalReport1->year)->get()->first();
                            if ($monthlyStockData !== null) {
                                $monthlyStockData->update([
                                    'operating_income_3_monthly' => $codalReport1->operating_income ,
                                    'net_profit_and_loss_3_monthly' => $codalReport1->net_profit_loss,
                                    'production_cost_3_monthly' => $codalReport1->cost_of_goods_sold,
                                ]);
                                error_log(" 444444 => " );

                            }else {
                                $this->saveMonthlyStockData($codalReport1->year , $codalReport1->stock_id);
                                $monthlyStockData = MonthlyStockData::where('stock_id', $codalReport1->stock_id)->where('year', $codalReport1->year)->get()->first();

                                $monthlyStockData->update([
                                        'operating_income_3_monthly' => $codalReport1->operating_income ,
                                        'net_profit_and_loss_3_monthly' => $codalReport1->net_profit_loss,
                                        'production_cost_3_monthly' => $codalReport1->cost_of_goods_sold,
                                ]);
                                error_log(" 55555 => " );

                            }

                            $this->calculateForecastPriceData->updateForecastPriceData($codalReport1->stock_id);
                            $reportQueue->update([
                                'status' => "success",
                            ]);
                        }else{
                            error_log(" 6666666666 => " );

                            $reportQueue->update([
                                'status' => "failed-Sheet20-problem",
                            ]);
                        }
                    }else{
                        error_log(" 77777777777777 => " );

                        $reportQueue->update([
                            'status' => "failed-SheetZero-problem",
                        ]);
                    }
                }else{
                    error_log(" 88888888 => " );

                    $reportQueue->update([
                        'status' => "failed-SheetOne-problem",
                    ]);
                }
            }else {
                error_log(" 9999999 => " );

                $reportQueue->update([
                    'status' => "failed-downloaded-before",
                ]);
            }


    }


    private function getCodal_6_monthData(ReportQueue $reportQueue){

            $codalReport = CodalReport::where('tracing_no', $reportQueue->tracing_no)->get()->first();
            if ($codalReport == null) {

                $codalReport1 = new CodalReport();
                $codalReport1->report_period = 6 ;
                $codalReport1 = $this->getCodalReportForSheetOne($codalReport1 , $reportQueue->url);

                if ($codalReport1 !== 'hasProblem') {

                    $codalReport1 = $this->getCodalReportForSheetZero($codalReport1 , $reportQueue->url);

                    if ($codalReport1 !== 'hasProblem') {

                        $codalReport1 = $this->getCodalReportForSheet20($codalReport1 , $reportQueue->url);
                        if ($codalReport1 !== 'hasProblem') {
                            $codalReport1->stock_id = $reportQueue->stock->id;
                            $codalReport1->save();
                            $monthlyStockData = MonthlyStockData::where('stock_id', $codalReport1->stock_id)->where('year', $codalReport1->year)->get()->first();
                            if ($monthlyStockData !== null) {
                                $monthlyStockData->update([
                                    'operating_income_6_monthly' => $codalReport1->operating_income ,
                                    'net_profit_and_loss_6_monthly' => $codalReport1->net_profit_loss,
                                    'production_cost_6_monthly' => $codalReport1->cost_of_goods_sold,
                                ]);
                            }else {
                                $this->saveMonthlyStockData($codalReport1->year , $codalReport1->stock_id);
                                $monthlyStockData = MonthlyStockData::where('stock_id', $codalReport1->stock_id)->where('year', $codalReport1->year)->get()->first();

                                $monthlyStockData->update([
                                    'operating_income_6_monthly' => $codalReport1->operating_income ,
                                    'net_profit_and_loss_6_monthly' => $codalReport1->net_profit_loss,
                                    'production_cost_6_monthly' => $codalReport1->cost_of_goods_sold,
                                ]);
                            }
                            $this->calculateForecastPriceData->updateForecastPriceData($codalReport1->stock_id);

                            $reportQueue->update([
                                'status' => "success",
                            ]);
                        }else{
                            $reportQueue->update([
                                'status' => "failed-Sheet20-problem",
                            ]);
                        }
                    }else{
                        $reportQueue->update([
                            'status' => "failed-SheetZero-problem",
                        ]);
                    }
                }else{
                    $reportQueue->update([
                        'status' => "failed-SheetOne-problem",
                    ]);
                }
            }else {
                $reportQueue->update([
                    'status' => "failed-downloaded-before",
                ]);
            }


    }

    private function getCodal_9_monthData(ReportQueue $reportQueue){

            $codalReport = CodalReport::where('tracing_no', $reportQueue->tracing_no)->get()->first();
            if ($codalReport == null) {

                $codalReport1 = new CodalReport();
                $codalReport1->report_period = 9 ;
                $codalReport1 = $this->getCodalReportForSheetOne($codalReport1 , $reportQueue->url);

                if ($codalReport1 !== 'hasProblem') {

                    $codalReport1 = $this->getCodalReportForSheetZero($codalReport1 , $reportQueue->url);

                    if ($codalReport1 !== 'hasProblem') {

                        $codalReport1 = $this->getCodalReportForSheet20($codalReport1 , $reportQueue->url);
                        if ($codalReport1 !== 'hasProblem') {
                            $codalReport1->stock_id =$reportQueue->stock->id;
                            $codalReport1->save();
                            $monthlyStockData = MonthlyStockData::where('stock_id', $codalReport1->stock_id)->where('year', $codalReport1->year)->get()->first();
                            if ($monthlyStockData !== null) {
                                $monthlyStockData->update([
                                    'operating_income_9_monthly' => $codalReport1->operating_income ,
                                    'net_profit_and_loss_9_monthly' => $codalReport1->net_profit_loss,
                                    'production_cost_9_monthly' => $codalReport1->cost_of_goods_sold,
                                ]);
                            }else {
                                $this->saveMonthlyStockData($codalReport1->year , $codalReport1->stock_id);
                                $monthlyStockData = MonthlyStockData::where('stock_id', $codalReport1->stock_id)->where('year', $codalReport1->year)->get()->first();

                                $monthlyStockData->update([
                                    'operating_income_9_monthly' => $codalReport1->operating_income ,
                                    'net_profit_and_loss_9_monthly' => $codalReport1->net_profit_loss,
                                    'production_cost_9_monthly' => $codalReport1->cost_of_goods_sold,
                                ]);
                            }
                            $this->calculateForecastPriceData->updateForecastPriceData($codalReport1->stock_id);

                            $reportQueue->update([
                                'status' => "success",
                            ]);
                        }else{
                            $reportQueue->update([
                                'status' => "failed-Sheet20-problem",
                            ]);
                        }
                    }else{
                        $reportQueue->update([
                            'status' => "failed-SheetZero-problem",
                        ]);
                    }
                }else{
                    $reportQueue->update([
                        'status' => "failed-SheetOne-problem",
                    ]);
                }
            }else {
                $reportQueue->update([
                    'status' => "failed-downloaded-before",
                ]);
            }

    }

    private function getCodal_12_monthData( ReportQueue $reportQueue){

            $codalReport = CodalReport::where('tracing_no', $reportQueue->tracing_no)->get()->first();
            if ($codalReport == null) {

                $codalReport1 = new CodalReport();
                $codalReport1->report_period = 12 ;
                $codalReport1 = $this->getCodalReportForSheetOne($codalReport1 , $reportQueue->url);

                if ($codalReport1 !== 'hasProblem') {

                    $codalReport1 = $this->getCodalReportForSheetZero($codalReport1 , $reportQueue->url);

                    if ($codalReport1 !== 'hasProblem') {

                        $codalReport1 = $this->getCodalReportForSheet20($codalReport1 , $reportQueue->url);
                        if ($codalReport1 !== 'hasProblem') {
                            $codalReport1->stock_id = $reportQueue->stock->id;
                            $codalReport1->save();
                            $dollarPrice = DollarPrice::where("year", $codalReport1->year  )->get()->first();
                            $yearlyStockData = new  YearlyStockData();
                            $yearlyStockData->year = $codalReport1->year  ; // سال مالی
                            $yearlyStockData->operating_income = $codalReport1 -> operating_income; // درآمد عملیاتی
                            $yearlyStockData->net_profit_and_loss = $codalReport1->net_profit_loss ; // سود و زیان خالس
                            $yearlyStockData->production_cost = $codalReport1->cost_of_goods_sold ; // هزینه تولید
                            $yearlyStockData->stock_id = $codalReport1->stock_id ;
                            $yearlyStockData->dollar_price_id  = $dollarPrice->id ;


                            $yearlyStockData->operating_income_in_dollars = $yearlyStockData->operating_income / $dollarPrice->price ;
                            $yearlyStockData->production_cost_in_dollars = $yearlyStockData->production_cost / $dollarPrice->price ;
                            $yearlyStockData->net_profit_and_loss_in_dollars = $yearlyStockData->net_profit_and_loss / $dollarPrice->price ;
                            $yearlyStockData->cost_of_production_to_sales = $yearlyStockData->production_cost /  $yearlyStockData->operating_income ;
                            $yearlyStockData->net_profit_margin = $yearlyStockData->net_profit_and_loss / $yearlyStockData->operating_income ;

                            $beforeDollarPrice = DollarPrice::where("year", ($codalReport1->year - 1) )->get()->first();
                            if ($beforeDollarPrice !== null) {
                                $yearlyStockData->dollar_changes = ($dollarPrice->price - $beforeDollarPrice->price ) / $beforeDollarPrice->price ;
                            }else {
                                $yearlyStockData->dollar_changes = 0 ;
                            }

                            $beforeYearlyStockData = YearlyStockData::where("stock_id", $yearlyStockData->stock_id )->where("year", $codalReport1->year - 1 )->get()->first();

                            if ($beforeYearlyStockData !== null && $yearlyStockData->net_profit_and_loss_in_dollars !== 0 ) {
                                $yearlyStockData->operating_income_in_dollars_changes = ($yearlyStockData->operating_income_in_dollars / $beforeYearlyStockData->operating_income_in_dollars) /  $beforeYearlyStockData->operating_income_in_dollars ;
                                $yearlyStockData->production_cost_in_dollars_changes = ($yearlyStockData->production_cost_in_dollars / $beforeYearlyStockData->production_cost_in_dollars) /  $beforeYearlyStockData->production_cost_in_dollars ;
                                $yearlyStockData->net_profit_and_loss_in_dollars_changes = ($yearlyStockData->net_profit_and_loss_in_dollars / $beforeYearlyStockData->net_profit_and_loss_in_dollars) /  $beforeYearlyStockData->net_profit_and_loss_in_dollars ;
                                $yearlyStockData->leverage = $yearlyStockData->net_profit_and_loss_in_dollars_changes / $yearlyStockData->operating_income_in_dollars_changes ;

                            }else {
                                $afterYearlyStockData = YearlyStockData::where("stock_id", $yearlyStockData->stock_id )->where("year", $codalReport1->year + 1 )->get()->first();
                                if ($afterYearlyStockData !== null && $yearlyStockData->operating_income_in_dollars == 0 && $yearlyStockData->net_profit_and_loss_in_dollars == 0 ) {

                                    $afterYearlyStockData->update([
                                        'operating_income_in_dollars_changes' => ($afterYearlyStockData->operating_income_in_dollars / $yearlyStockData->operating_income_in_dollars) /  $yearlyStockData->operating_income_in_dollars,
                                        'production_cost_in_dollars_changes' => ($afterYearlyStockData->production_cost_in_dollars / $yearlyStockData->production_cost_in_dollars) /  $yearlyStockData->production_cost_in_dollars,
                                        'net_profit_and_loss_in_dollars_changes' => ($afterYearlyStockData->net_profit_and_loss_in_dollars / $yearlyStockData->net_profit_and_loss_in_dollars) /  $yearlyStockData->net_profit_and_loss_in_dollars,
                                        'leverage' => (($afterYearlyStockData->net_profit_and_loss_in_dollars / $yearlyStockData->net_profit_and_loss_in_dollars) /  $yearlyStockData->net_profit_and_loss_in_dollars) /  (($afterYearlyStockData->operating_income_in_dollars / $yearlyStockData->operating_income_in_dollars) /  $yearlyStockData->operating_income_in_dollars),
                                    ]);
                                }

                            }
                            $yearlyStockData->save();

                            $monthlyStockData = MonthlyStockData::where('stock_id', $yearlyStockData->stock_id)->where('year', $yearlyStockData->year)->get()->first();
                            if ($monthlyStockData !== null) {
                                $monthlyStockData->update([
                                    'operating_income_12_monthly' => $yearlyStockData->operating_income ,
                                    'net_profit_and_loss_12_monthly' => $yearlyStockData->net_profit_and_loss,
                                    'production_cost_12_monthly' => $yearlyStockData->production_cost,
                                ]);
                            }else {
                                $this->saveMonthlyStockData($yearlyStockData->year , $yearlyStockData->stock_id);
                                $monthlyStockData = MonthlyStockData::where('stock_id', $yearlyStockData->stock_id)->where('year', $yearlyStockData->year)->get()->first();

                                $monthlyStockData->update([
                                    'operating_income_12_monthly' => $yearlyStockData->operating_income ,
                                    'net_profit_and_loss_12_monthly' => $yearlyStockData->net_profit_and_loss,
                                    'production_cost_12_monthly' => $yearlyStockData->production_cost,
                                ]);
                            }

                            error_log('save data  for   ' .$reportQueue->tracing_no . "  " . $yearlyStockData->year);

                            $this->calculateForecastPriceData->updateForecastPriceData($yearlyStockData->stock_id);


                            $reportQueue->update([
                                'status' => "success",
                            ]);
                        }else{
                            $reportQueue->update([
                                'status' => "failed-Sheet20-problem",
                            ]);
                        }
                    }else{
                        $reportQueue->update([
                            'status' => "failed-SheetZero-problem",
                        ]);
                    }
                }else{
                    $reportQueue->update([
                        'status' => "failed-SheetOne-problem",
                    ]);
                }
            }else {
                $reportQueue->update([
                    'status' => "failed-downloaded-before",
                ]);
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
            $codalReport->year  = explode("/", $codalReport->year_end_to_date)[0] ;
            $codalReport->period_end_to_date = $datasourceArray['periodEndToDate'];

            $sheet = $datasourceArray['sheets'][0] ;

            if ($sheet['code'] !== 1){
                return "hasProblem";
            }
            if (count($sheet["tables"]) == 0){
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

                if ($cell['rowCode'] === 31 &&  $cell['columnCode'] === 3  ){
                    $codalReport->first_period_finished_goods_inventory = $cell['value'];
                }

                if ($cell['rowCode'] === 32  &&  $cell['columnCode'] === 3  ){
                    $codalReport->ending_period_finished_goods_inventory = $cell['value'];
                }

                if ($cell['rowCode'] === 33  &&  $cell['columnCode'] === 3  ){
                    $codalReport->cost_of_goods_sold = $cell['value'];

                }

            }

            return $codalReport ;

        }else{
            return "hasProblem";
        }

    }

}
