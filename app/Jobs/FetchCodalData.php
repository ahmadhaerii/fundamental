<?php

namespace App\Jobs;

use App\Models\ReportQueue;
use App\Models\Stock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class FetchCodalData implements ShouldQueue
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
       $stock =  Stock::with('stockDailyCheckUrl')->orderBy('update_time', 'asc')->firstOrFail();
        if ($stock == null) {
            return "سهم پیدا نشد";
        }
        error_log('start job for  ' . $stock->name);

        $this->getStock($stock);


//       self::dispatch()->delay(now()->addSeconds(300));
    }


    public function getStock(Stock $stock) {

//        $response = Http::get('https://cdn.tsetmc.com/api/Instrument/GetInstrumentInfo/778253364357513');
//        $response = Http::get('https://cdn.tsetmc.com/api/ClosingPrice/GetClosingPriceInfo/778253364357513');


        $this->getCodal_1_monthData($stock);
        $this->getCodal_3_monthData($stock);
        $this->getCodal_6_monthData($stock);
        $this->getCodal_9_monthData( $stock);
        $this->getCodal_12_monthData($stock);

        return  'done';

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
                $reportQueue = ReportQueue::where('tracing_no', $letter["TracingNo"])->get()->first();

                if ($reportQueue == null) {
                    $reportQueue = new ReportQueue();
                    $reportQueue->tracing_no = $letter["TracingNo"];
                    $reportQueue->url =  'https://www.codal.ir' . $letter["Url"];
                    $reportQueue->report_type = "m1";
                    $reportQueue->status = 'pending';
                    $reportQueue->stock_id = $stock->id;
                    $reportQueue->save();
                }
            }
        }

        return  "DONE" ;
    }

    private function getCodal_3_monthData(Stock $stock){

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get($stock->stockDailyCheckUrl->codal_3_month);

        $codalData = $response->json();

        foreach ($codalData['Letters'] as $letter) {
            $reportQueue = ReportQueue::where('tracing_no', $letter["TracingNo"])->get()->first();

            if ($reportQueue == null) {
                $reportQueue = new ReportQueue();
                $reportQueue->tracing_no = $letter["TracingNo"];
                $reportQueue->url =  'https://www.codal.ir' . $letter["Url"];
                $reportQueue->report_type = "m3";
                $reportQueue->status = 'pending';
                $reportQueue->stock_id = $stock->id;
                $reportQueue->save();
            }

        }
        return  "DONE" ;
    }


    private function getCodal_6_monthData( Stock $stock){

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get($stock->stockDailyCheckUrl->codal_6_month);

        $codalData = $response->json();

        foreach ($codalData['Letters'] as $letter) {
            $reportQueue = ReportQueue::where('tracing_no', $letter["TracingNo"])->get()->first();

            if ($reportQueue == null) {
                $reportQueue = new ReportQueue();
                $reportQueue->tracing_no = $letter["TracingNo"];
                $reportQueue->url =  'https://www.codal.ir' . $letter["Url"];
                $reportQueue->report_type = "m6";
                $reportQueue->status = 'pending';
                $reportQueue->stock_id = $stock->id;
                $reportQueue->save();
            }

        }
    }

    private function getCodal_9_monthData( Stock $stock){

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get($stock->stockDailyCheckUrl->codal_9_month);

        $codalData = $response->json();

        foreach ($codalData['Letters'] as $letter) {
            $reportQueue = ReportQueue::where('tracing_no', $letter["TracingNo"])->get()->first();

            if ($reportQueue == null) {
                $reportQueue = new ReportQueue();
                $reportQueue->tracing_no = $letter["TracingNo"];
                $reportQueue->url =  'https://www.codal.ir' . $letter["Url"];
                $reportQueue->report_type = "m9";
                $reportQueue->status = 'pending';
                $reportQueue->stock_id = $stock->id;
                $reportQueue->save();
            }

        }
    }

    private function getCodal_12_monthData( Stock $stock){

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get($stock->stockDailyCheckUrl->codal_12_month);

        $codalData = $response->json();

        foreach ($codalData['Letters'] as $letter) {
            $reportQueue = ReportQueue::where('tracing_no', $letter["TracingNo"])->get()->first();

            if ($reportQueue == null) {
                $reportQueue = new ReportQueue();
                $reportQueue->tracing_no = $letter["TracingNo"];
                $reportQueue->url =  'https://www.codal.ir' . $letter["Url"];
                $reportQueue->report_type = "m12";
                $reportQueue->status = 'pending';
                $reportQueue->stock_id = $stock->id;
                $reportQueue->save();
            }

        }
    }

}
