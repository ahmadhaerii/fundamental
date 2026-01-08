<?php
namespace App\Services;

use App\Models\ForecastPrice;
use App\Models\MonthlyStockData;
use App\Models\Stock;
use Illuminate\Support\Facades\Http;

class CalculateForecastPriceData {
    protected int $inflationRate = 40 ;
    public function calculate( int  $stockId) {
        $stock = Stock::find($stockId)->with('category','monthlyStockData' ,'yearlyStockData.dollarPrice','lastForecastPrice')->firstOrFail();
        $currentYear = jdate()->format('%Y');
        $forwardMonthlyStockData = null ;
        foreach ($stock->monthlyStockData as $monthlyStockData) {
            if ($monthlyStockData->year == $currentYear) {
                $lastReportMonth = $this->lastReportMonth($monthlyStockData);
                $inflation = (($this->inflationRate / 12 ) / 100 ) + 1 ;

                if (
                    $lastReportMonth >= 1 &&
                    $lastReportMonth <= 5
                ) {
                    if ($monthlyStockData->m1 == 0) {
                        return;
                    }
                    if ($monthlyStockData->m2 == 0) {
                        $monthlyStockData->m2 =round($monthlyStockData->m1 *  $inflation)
                        ;
                    }
                    if ($monthlyStockData->m3 == 0) {
                        $monthlyStockData->m3 =round( (($monthlyStockData->m1 + $monthlyStockData->m2) / 2 ) *  $inflation);
                    }
                    if ($monthlyStockData->m4 == 0) {
                        $monthlyStockData->m4 =round(
                                (($monthlyStockData->m1 + $monthlyStockData->m2 + $monthlyStockData->m3) / 3)  *  $inflation);
                    }
                    if ($monthlyStockData->m5 == 0) {
                        $monthlyStockData->m5 =round(
                                ( ($monthlyStockData->m1 +
                                        $monthlyStockData->m2 +
                                        $monthlyStockData->m3 +
                                        $monthlyStockData->m4) /
                                    4) * $inflation);
                    }
                    $average =  ($monthlyStockData->m1 + $monthlyStockData->m2 + $monthlyStockData->m3 + $monthlyStockData->m4  + $monthlyStockData->m5) / 5  ;
                    $monthlyStockData->m6 =round( $average *  $inflation);
                    $monthlyStockData->m7 =round( $average *  $inflation);
                    $monthlyStockData->m8 =round( $average *  $inflation);
                    $monthlyStockData->m9 =round( $average *  $inflation);
                    $monthlyStockData->m10 =round( $average *  $inflation);
                    $monthlyStockData->m11 =round( $average *  $inflation);
                    $monthlyStockData->m12 =round( $average *  $inflation);

                } else if (
                    $lastReportMonth >= 6 &&
                    $lastReportMonth <= 12
                ) {
                    $inflation =   1 ;

                    if ($monthlyStockData->m6 == 0) {
                        $average =  ($monthlyStockData->m1 + $monthlyStockData->m2 + $monthlyStockData->m3 + $monthlyStockData->m4  + $monthlyStockData->m5) / 5  ;
                        $monthlyStockData->m6 = round($average *  $inflation);

                    }
                    if ($monthlyStockData->m7 == 0) {
                        $average =  ($monthlyStockData->m1 + $monthlyStockData->m2 + $monthlyStockData->m3 + $monthlyStockData->m4  + $monthlyStockData->m5 + $monthlyStockData->m6) / 6  ;
                        $monthlyStockData->m7 = round($average *  $inflation);

                    }
                    if ($monthlyStockData->m8 == 0) {
                        $average =  ($monthlyStockData->m1 + $monthlyStockData->m2 + $monthlyStockData->m3 + $monthlyStockData->m4  + $monthlyStockData->m5 + $monthlyStockData->m6  + $monthlyStockData->m7) / 7  ;
                        $monthlyStockData->m8 = round($average *  $inflation);

                    }
                    if ($monthlyStockData->m9 == 0) {
                        $average =  ($monthlyStockData->m1 + $monthlyStockData->m2 + $monthlyStockData->m3 + $monthlyStockData->m4  + $monthlyStockData->m5 + $monthlyStockData->m6 +  $monthlyStockData->m7 +  $monthlyStockData->m8) / 8  ;
                        $monthlyStockData->m9 = round($average *  $inflation);

                    }
                    if ($monthlyStockData->m10 == 0) {
                        $average =  ($monthlyStockData->m1 + $monthlyStockData->m2 + $monthlyStockData->m3 + $monthlyStockData->m4  + $monthlyStockData->m5 + $monthlyStockData->m6 +  $monthlyStockData->m7 +  $monthlyStockData->m8+  $monthlyStockData->m9) / 9  ;
                        $monthlyStockData->m10 = round($average *  $inflation);

                    }
                    if ($monthlyStockData->m11 == 0) {
                        $average =  ($monthlyStockData->m1 + $monthlyStockData->m2 + $monthlyStockData->m3 + $monthlyStockData->m4  + $monthlyStockData->m5 + $monthlyStockData->m6 +  $monthlyStockData->m7 +  $monthlyStockData->m8+  $monthlyStockData->m9+  $monthlyStockData->m10) / 10  ;
                        $monthlyStockData->m11 = round($average *  $inflation);

                    }
                    if ($monthlyStockData->m12 == 0) {
                        $average =  ($monthlyStockData->m1 + $monthlyStockData->m2 + $monthlyStockData->m3 + $monthlyStockData->m4  + $monthlyStockData->m5 + $monthlyStockData->m6 +  $monthlyStockData->m7+  $monthlyStockData->m8+  $monthlyStockData->m9+  $monthlyStockData->m10+  $monthlyStockData->m11) / 11 ;
                        $monthlyStockData->m12 = round($average *  $inflation);

                    }

                }
                if(  $lastReportMonth <=3){
                    // $monthlyStockData->operating_income_3_monthly = get from before year
                }
                $netProfitMarginFirstSeason = $monthlyStockData->net_profit_and_loss_3_monthly / $monthlyStockData->operating_income_3_monthly ;
                $netProfitMarginSecondSeason = 0 ;

                if(  $lastReportMonth >3 &&   $lastReportMonth <= 6  ){
                    $monthlyStockData->operating_income_6_monthly = $monthlyStockData->operating_income_3_monthly + ( $monthlyStockData->m4 +  $monthlyStockData->m5 +  $monthlyStockData->m6) ;
                    $monthlyStockData->operating_income_9_monthly = $monthlyStockData->operating_income_6_monthly + ($monthlyStockData->m7 +  $monthlyStockData->m8 +  $monthlyStockData->m9) ;
                    $monthlyStockData->operating_income_12_monthly = $monthlyStockData->operating_income_9_monthly + ($monthlyStockData->m10 +  $monthlyStockData->m11 +  $monthlyStockData->m12) ;
                    $netProfitMarginFirstSeason = $monthlyStockData->net_profit_and_loss_3_monthly / $monthlyStockData->operating_income_3_monthly ;

                    $monthlyStockData->net_profit_and_loss_6_monthly = round($monthlyStockData->operating_income_6_monthly *  $netProfitMarginFirstSeason ) ;
                    $monthlyStockData->net_profit_and_loss_9_monthly = round($monthlyStockData->operating_income_9_monthly * $netProfitMarginFirstSeason );
                    $monthlyStockData->net_profit_and_loss_12_monthly =round( $monthlyStockData->operating_income_12_monthly * $netProfitMarginFirstSeason );

                }


                if(  $lastReportMonth > 6 &&   $lastReportMonth <= 9  ){
                    $monthlyStockData->operating_income_9_monthly =round( $monthlyStockData->operating_income_6_monthly + ($monthlyStockData->m7 +  $monthlyStockData->m8 +  $monthlyStockData->m9) ) ;
                    $monthlyStockData->operating_income_12_monthly = round($monthlyStockData->operating_income_9_monthly + ($monthlyStockData->m10 +  $monthlyStockData->m11 +  $monthlyStockData->m12)) ;
                    $netProfitMarginSecondSeason = ($monthlyStockData->net_profit_and_loss_6_monthly  - $monthlyStockData->net_profit_and_loss_3_monthly) / ( $monthlyStockData->operating_income_6_monthly  - $monthlyStockData->operating_income_3_monthly);

                    $netProfitMarginSeason = ($netProfitMarginFirstSeason +  $netProfitMarginSecondSeason ) / 2 ;
                    $monthlyStockData->net_profit_and_loss_9_monthly = round($monthlyStockData->operating_income_9_monthly * $netProfitMarginSeason );
                    $monthlyStockData->net_profit_and_loss_12_monthly = round($monthlyStockData->operating_income_12_monthly * $netProfitMarginSeason );
                }

                if(  $lastReportMonth >9 &&   $lastReportMonth <= 12  ){
                    $monthlyStockData->operating_income_12_monthly = round($monthlyStockData->operating_income_6_monthly + ($monthlyStockData->m10 +  $monthlyStockData->m11 +  $monthlyStockData->m12)) ;
                    $netProfitMarginThirdSeason = ($monthlyStockData->net_profit_and_loss_9_monthly  - $monthlyStockData->net_profit_and_loss_6_monthly) / ( $monthlyStockData->operating_income_9_monthly  - $monthlyStockData->operating_income_6_monthly);

                    $netProfitMarginSeason = ($netProfitMarginFirstSeason + $netProfitMarginSecondSeason + $netProfitMarginThirdSeason  ) / 3 ;
                    $monthlyStockData->net_profit_and_loss_12_monthly = round($monthlyStockData->operating_income_12_monthly * $netProfitMarginSeason );

                }


                $forwardMonthlyStockData =  $monthlyStockData ;
            }
        }

        return $forwardMonthlyStockData ;
    }

    public function updateForecastPriceData( int  $stockId) {
        $stock = Stock::find($stockId)->firstOrFail();
        $forwardMonthlyStockData =  $this->calculate($stockId) ;
        if ($forwardMonthlyStockData != null){

            $forecastPrice = new ForecastPrice();
            $forecastPrice->stock_id= $stockId ;
            $forecastPrice->year= $forwardMonthlyStockData->year ;
            $forecastPrice->price= $stock->last_price   ;
            $forecastPrice->number_of_shares= $stock->number_of_shares ;

            $seasonData = $this->getSeasonData($forwardMonthlyStockData) ;
            $totalProfit = (($seasonData->netProfitAndLossFirstSeason + $seasonData->netProfitAndLossSecondSeason + $seasonData->netProfitAndLossThirdSeason + $seasonData->netProfitAndLossFourthSeason ) * 1000000   ) ;
            $forecastPrice->total_profit= $totalProfit ;
            $forecastPrice->operating_income=  ''  ;
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
