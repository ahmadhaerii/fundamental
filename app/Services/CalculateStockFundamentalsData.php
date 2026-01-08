<?php
namespace App\Services;

use App\Models\CodalReport;
use App\Models\ForecastPrice;
use App\Models\MonthlyStockData;
use App\Models\OrdinaryGeneralAssemblyResolution;
use App\Models\Stock;
use App\Models\StockFundamental;
use Illuminate\Support\Facades\Http;
use function Illuminate\Events\queueable;

class CalculateStockFundamentalsData {

    public function __construct(
        protected CalculateForecastPriceData $calculateForecastPriceData,
    ) {}

    public function calculate( int  $stockId) {
        $stockFundamental = new StockFundamental();
        $lastCodalReport = CodalReport::where("stock_id" , $stockId )->where('report_period', 12)->get()->last();
        $stockFundamental->year = $lastCodalReport->year;
        $stockFundamental->inventory_turnover_period =
            (( ( $lastCodalReport->inventory_of_materials_and_goods_current_period  + $lastCodalReport->inventory_of_materials_and_goods_prior_period) / 2)
                /  $lastCodalReport->cost_of_goods_sold ) * 365;

        $stockFundamental->receivables_collection_period= (
            (($lastCodalReport->trade_and_other_receivables_current_period + $lastCodalReport->trade_and_other_receivables_prior_period ) / 2 )
            / $lastCodalReport->operating_income ) * 365 ;
        $stockFundamental->profit_margin =  ($lastCodalReport->net_profit_and_loss / $lastCodalReport->operating_income ) * 100 ;
        $stockFundamental->sales_growth_rate = ( $lastCodalReport->operating_income_prior_period / $lastCodalReport->operating_income) * 100  ;

        $stockFundamental->current_ratio =  $lastCodalReport->total_current_assets / $lastCodalReport->total_current_liabilities ;
        $stockFundamental->quick_ratio =
            ($lastCodalReport->total_current_assets - $lastCodalReport->orders_and_prepayments - $lastCodalReport->inventory_of_materials_and_goods_current_period  )
            /  $lastCodalReport->total_current_liabilities ;

        $stockFundamental->total_asset_turnover_ratio =  $lastCodalReport->operating_income /  $lastCodalReport->total_assets ;
        $stockFundamental->fixed_asset_turnover_ratio =  $lastCodalReport->operating_income /  $lastCodalReport->total_none_current_assets ;

        $stockFundamental->inventory_to_working_capital_ratio =
            ( $lastCodalReport->operating_income / ( ( $lastCodalReport->trade_and_other_receivables_current_period  + $lastCodalReport->trade_and_other_receivables_prior_period) / 2) );

        $stockFundamental->operating_cycle_ratio = $stockFundamental->inventory_turnover_period +  $stockFundamental->receivables_collection_period ;
        $stockFundamental->long_term_debt_to_equity_ratio = $lastCodalReport->total_non_current_liabilities / $lastCodalReport->total_shareholders_equity  ;
        $stockFundamental->total_debt_to_total_assets_ratio =   $lastCodalReport->total_liabilities /  $lastCodalReport->total_assets ;
        $stockFundamental->current_debt_to_equity_ratio =  $lastCodalReport->total_current_liabilities / $lastCodalReport->total_shareholders_equity ;
        $stockFundamental->debt_to_equity_ratio = $lastCodalReport->total_liabilities / $lastCodalReport->total_shareholders_equity   ;

        $stockFundamental->net_profit_margin = $lastCodalReport->gross_profit_and_loss / $lastCodalReport->operating_income  ;
        $stockFundamental->operating_profit_margin =   $lastCodalReport->operating_profit_and_loss / $lastCodalReport->operating_income  ;
        $stockFundamental->return_on_sales =    $lastCodalReport->net_profit_and_loss / $lastCodalReport->operating_income  ;
        $stockFundamental->return_on_equity =  $lastCodalReport->net_profit_and_loss / $lastCodalReport->total_shareholders_equity  ;
        $stockFundamental->return_on_working_capital = $lastCodalReport->net_profit_and_loss  / ( $lastCodalReport->total_current_assets - $lastCodalReport->total_current_liabilities) ;
        $stockFundamental->fixed_assets_to_equity_ratio =  $lastCodalReport->total_none_current_assets / $lastCodalReport->total_shareholders_equity  ;
        $stockFundamental->return_on_assets =  $lastCodalReport->net_profit_and_loss / $lastCodalReport->total_assets ;
        $stockFundamental->equity_ratio =  $lastCodalReport->total_shareholders_equity /  $lastCodalReport->total_assets ;
        $stockFundamental->dividend_payout = 0 ;

        $stockFundamental->stock_id = $stockId ;
        return $stockFundamental->save();
    }


}
