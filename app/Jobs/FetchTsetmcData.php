<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\CodalReport;
use App\Models\PriceBasedValuationRatio;
use App\Models\ReportQueue;
use App\Models\Stock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class FetchTsetmcData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }


    public function handle()
    {
        error_log('FetchTsetmcData '  );

        $stock =  Stock::with('stockDailyCheckUrl')->orderBy('update_time', 'DESC')->get()->last();
        if ($stock == null) {
            error_log("سهم پیدا نشد");
            return "سهم پیدا نشد";
        }
        error_log('start job for  ' . $stock->name);

        $this->getStockData($stock);
        $stock->update([
            'update_time' => now() ,
        ]);

       self::dispatch()->delay(now()->addSeconds(80000));
    }


    public function getStockData(Stock $stock) {

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get('https://cdn.tsetmc.com/api/ClosingPrice/GetClosingPriceInfo/'. $stock->tsetmc_id );
        $tsetmcData = $response->body();
        $tsetmcData = json_decode($tsetmcData , true );

        $response2 = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get('https://cdn.tsetmc.com/api/Instrument/GetInstrumentInfo/'. $stock->tsetmc_id );
        $tsetmcData2 = $response2->body();
        $tsetmcData2 = json_decode($tsetmcData2 , true );

        $category =  Category::where('name' , $tsetmcData2['instrumentInfo']['sector']['lSecVal'] )->get()->first();
        if ($category == null) {
            $category = new Category();
            $category->name = $tsetmcData2['instrumentInfo']['sector']['lSecVal'];
            $category->description = $tsetmcData2['instrumentInfo']['sector']['cSecVal'];
            $category->save();
        }
        $category =  Category::where('name' , $tsetmcData2['instrumentInfo']['sector']['lSecVal'] )->get()->first();
        error_log( $stock->name . ' =>  ' .$tsetmcData['closingPriceInfo']['pClosing']);

        $stock->update([
            'p_e_ttm' =>  $tsetmcData['closingPriceInfo']['pClosing'] / $tsetmcData2['instrumentInfo']['eps']['estimatedEPS'] ,
            'full_name' =>  $tsetmcData2['instrumentInfo']['lVal30'] ,
            'name' =>  $tsetmcData2['instrumentInfo']['lVal18AFC'] ,
            'last_price' =>   $tsetmcData['closingPriceInfo']['pClosing'],
            'number_of_shares' => $tsetmcData2['instrumentInfo']['zTitad']  ,
            'category_id' => $category->id ,
            'update_time' => now() ,
        ]);

        $priceBasedValuationRatio =  PriceBasedValuationRatio::where('stock_id' , $stock->id )->get()->first();
        $lastCodalReportYearly =  CodalReport::where('stock_id' , $stock->id )->where('report_period' , 12 )->get()->last();
        if ($lastCodalReportYearly != null) {
            $p_b =   $stock->last_price / (( $lastCodalReportYearly->total_shareholders_equity * 1000000)  / $stock->number_of_shares ) ;
            $p_s = $stock->last_price /  (( $lastCodalReportYearly->operating_income * 1000000) / $stock->number_of_shares );
            $p_e = $stock->p_e_ttm  ;
            $p_a = $stock->last_price /  (($lastCodalReportYearly->total_assets * 1000000) / $stock->number_of_shares);

            if ($priceBasedValuationRatio == null){
                $priceBasedValuationRatio = new PriceBasedValuationRatio();
                $priceBasedValuationRatio->p_b =  $p_b ;
                $priceBasedValuationRatio->p_s = $p_s;
                $priceBasedValuationRatio->p_e = $p_e  ;
                $priceBasedValuationRatio->p_a = $p_a ;
                $priceBasedValuationRatio->p_cf = 0 ;
                $priceBasedValuationRatio->p_cfc = 0;
                $priceBasedValuationRatio->stock_id = $stock->id ;
                $priceBasedValuationRatio->save();
            }else {
                $priceBasedValuationRatio->update([
                    'p_b' =>  $p_b ,
                    'p_s' => $p_s ,
                    'p_e' => $p_e   ,
                    'p_a' =>  $p_a  ,
                    'p_cf' =>   0  ,
                    'p_cfc' => 0 ,
                ]);
            }
        }

        return  'done';

    }

}
