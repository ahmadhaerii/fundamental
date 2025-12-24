<?php
namespace App\Services;

use App\Models\ForecastPrice;
use App\Models\MonthlyStockData;
use App\Models\Stock;
use Illuminate\Support\Facades\Http;
use function Illuminate\Events\queueable;

class CalculateStockFundamentalsData {


//inventory_turnover_period
//receivables_collection_period
//profit_margin
//estimated_sales_year_end
//estimated_net_profit_year_end
//market_cap
//sales_growth_rate
//receivables_ratio
//average_dividend_payout
//p_e_f
//p_s_f
//p_b
//p_a
//p_d_f




    protected int $inflationRate = 40 ;
    public function calculate( int  $stockId) {
        $stock = Stock::find($stockId)->with('category','monthlyStockData' ,'yearlyStockData.dollarPrice','lastForecastPrice')->firstOrFail();
        $currentYear = jdate()->format('%Y');
        $stockFundamental = StockFundamental();
        $lastCodalReport = $stockFundamental->getLastCodalReport();
        $OldCodalReport = "" ; // get CodalReport in 12 month past ;
        $stockFundamental->inventory_turnover_period =  ((($lastCodalReport->ending_period_finished_goods_inventory + $lastCodalReport->first_period_finished_goods_inventory) / 2) / $lastCodalReport->cost_of_goods_sold ) * 365 ;

        $stockFundamental->receivables_collection_period=  ($lastCodalReport->trade_and_other_receivables / $lastCodalReport->operating_income) * 365 ;
        $stockFundamental->profit_margin =  ($lastCodalReport->net_profit_loss / $lastCodalReport->operating_income ) * 100 ;
        $stockFundamental->estimated_sales_year_end = 0 ;
        $stockFundamental->estimated_net_profit_year_end =$lastCodalReport-> total_profit ;
        $stockFundamental->market_cap = $lastCodalReport->number_of_shares * $stock->last_price ;
        $stockFundamental->sales_growth_rate = ( $OldCodalReport->operating_income / $lastCodalReport->operating_income) * 100  ;
        $stockFundamental->receivables_ratio = ($lastCodalReport->trade_and_other_receivables / $lastCodalReport->operating_income) ;
        $stockFundamental->average_dividend_payout = 0 ; // get data from 3 years old for d_p
        $stockFundamental->p_e_f = $lastCodalReport->p_e_forward ;
        $stockFundamental->p_s_f =  $stock->last_price /  ( $lastCodalReport->operating_income / $lastCodalReport->number_of_shares ) ;
        $stockFundamental->p_b = $stockFundamental->market_cap / $lastCodalReport->total_equity ;
        $stockFundamental->r_o_e = ($lastCodalReport-> net_profit_loss / $lastCodalReport->total_equity) * 100  ;
        $stockFundamental->p_a  =  0 ;
        $stockFundamental->p_d_f  =  0 ;
        $stockFundamental->save();
    }

    public function updateForecastPriceData( int  $stockId) {
        $stock = Stock::find($stockId)->firstOrFail();
        $forwardMonthlyStockData =  $this->calculate($stockId) ;
        if ($forwardMonthlyStockData != null){

            $forecastPrice = new ForecastPrice();
            $forecastPrice->stock_id= $stockId ;
            $forecastPrice->year= $forwardMonthlyStockData->year ;
            $forecastPrice->price= 134550 ; // get data from ts
            $forecastPrice->number_of_shares= $stock->number_of_shares ;

            $seasonData = $this->getSeasonData($forwardMonthlyStockData) ;
            $totalProfit = (($seasonData->netProfitAndLossFirstSeason + $seasonData->netProfitAndLossSecondSeason + $seasonData->netProfitAndLossThirdSeason + $seasonData->netProfitAndLossFourthSeason ) * 1000000   ) ;
            $forecastPrice->profit_forward= $totalProfit ;
            $forecastPrice->p_e_forward= ( ($stock->number_of_shares * $forecastPrice->price ) /  $totalProfit  )  ;
            $forecastPrice->eps_forward= ( $totalProfit / $stock->number_of_shares  ) ;

            $forecastPrice->save();

        }
    }

    private function  getSeasonData(MonthlyStockData $forwardMonthlyStockData){
        $out = (object) [
            'netProfitAndLossFirstSeason' => 0,
            'netProfitAndLossSecondSeason' => 0,
            'netProfitAndLossThirdSeason' => 0,
            'netProfitAndLossFourthSeason' => 0,
        ];
        $out->netProfitAndLossFirstSeason =  $forwardMonthlyStockData->net_profit_and_loss_3_monthly;
        $out->netProfitAndLossSecondSeason =  $forwardMonthlyStockData->net_profit_and_loss_6_monthly  - $forwardMonthlyStockData->net_profit_and_loss_3_monthly  ;
        $out->netProfitAndLossThirdSeason =  $forwardMonthlyStockData->net_profit_and_loss_9_monthly  - $forwardMonthlyStockData->net_profit_and_loss_6_monthly  ;
        $out->netProfitAndLossFourthSeason =  $forwardMonthlyStockData->net_profit_and_loss_12_monthly  - $forwardMonthlyStockData->net_profit_and_loss_9_monthly  ;
        return $out ;
    }

    private function  lastReportMonth(MonthlyStockData $monthlyStockData){

        if ($monthlyStockData->m1 == 0) {
        return 1 ;
        }else if ($monthlyStockData->m2 == 0) {
            return 2 ;
        }else if ($monthlyStockData->m3 == 0) {
            return 3 ;
        }else if ($monthlyStockData->m4 == 0) {
            return 4 ;
        }else if ($monthlyStockData->m5 == 0) {
            return 5 ;
        }else if ($monthlyStockData->m6 == 0) {
            return 6 ;
        }else if ($monthlyStockData->m7 == 0) {
            return 7 ;
        }else if ($monthlyStockData->m8 == 0) {
            return 8 ;
        }else if ($monthlyStockData->m9 == 0) {
            return 9 ;
        }else if ($monthlyStockData->m10 == 0) {
            return 10 ;
        }else if ($monthlyStockData->m11 == 0) {
            return 11 ;
        }else if ($monthlyStockData->m12 == 0) {
            return 12 ;
        } else{
            return 13 ;
        }
    }


}
