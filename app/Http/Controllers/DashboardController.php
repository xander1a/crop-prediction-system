<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Region;
use App\Models\PredictionResult;
use App\Models\User_alerts;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index1()
    {
        // Total Crops Tracked
        $totalCrops = Crop::count();
        
        // Total Regions
        $totalRegions = Region::count();
        
        // Daily Predictions (predictions created today)
        $dailyPredictions = PredictionResult::whereDate('created_at', today())->count();
        
        // Total Predictions
        $totalPredictions = PredictionResult::count();
        
        
        
        // Price Trends Data (last 30 days average predictions)
        $trendData = [];
        $trendLabels = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $averagePrice = PredictionResult::whereDate('prediction_date', $date)
                                    ->avg('predicted_price');
            
            $trendData[] = $averagePrice ? round($averagePrice, 2) : 0;
            $trendLabels[] = $date->format('M d');
        }
        
        // Top 5 Crops by Prediction Count
        $topCrops = Crop::withCount('pricePredictions')
                       ->orderBy('price_predictions_count', 'desc')
                       ->take(5)
                       ->get();
        
     
        
        return view('dashboard', [
           'totalCrops' => $totalCrops,
           'totalRegions' => $totalRegions,
           'dailyPredictions' => $dailyPredictions,
           'totalPredictions' => $totalPredictions,

        ]);
    }

    public function index()
{
    $predictions = PredictionResult::latest('prediction_date')->get();
    return view('dashboard-index', compact('predictions'));
}



public function marketTrend()
{
      $trendData = DB::table('prediction_results')
        ->selectRaw('DATE(target_date) as date, market, AVG(predicted_price) as avg_price')
        ->groupBy('date', 'market')
        ->orderBy('date')
        ->get();

    // Debug: Check what dates you have
    // dd($trendData->pluck('date')->unique()->values()->toArray());

    $dates = $trendData->pluck('date')->unique()->values();
    $markets = $trendData->pluck('market')->unique();

    $datasets = $markets->map(function ($market) use ($trendData, $dates) {
        return [
            'label' => $market,
            'data' => $dates->map(function ($date) use ($trendData, $market) {
                $entry = $trendData->firstWhere(fn ($d) => $d->date == $date && $d->market == $market);
                return $entry ? round($entry->avg_price, 2) : null;
            }),
            'fill' => false,
        ];
    });

    // Summary cards
    $avgPrice = round($trendData->avg('avg_price'), 2);
    $topMarket = $trendData->groupBy('market')
        ->map(fn ($group) => $group->count())
        ->sortDesc()
        ->keys()
        ->first();

    return view('market.index', [
        'datasets' => $datasets->toJson(),
        'dates' => $dates->toJson(),
        'avgPrice' => $avgPrice,
        'topMarket' => $topMarket,
    ]);
}
}
