<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Region;
use App\Models\Crop_price;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarketController extends Controller
{
    public function index()
    {
        // Get top 5 crops by recent price change
        $topCrops = Crop_price::with('crop')
            ->select('crop_id', DB::raw('AVG(price) as avg_price'))
            ->whereDate('date_recorded', '>=', now()->subDays(7))
            ->groupBy('crop_id')
            ->orderByDesc('avg_price')
            ->take(5)
            ->get();

        // Get market summary stats
        $stats = [
            'total_crops' => Crop::count(),
            'active_regions' => Region::has('prices')->count(),
            'price_updates_today' => Crop_price::whereDate('created_at', today())->count(),
        ];

        return view('market.index', [
            'topCrops' => $topCrops,
            'stats' => $stats,
            'recentUpdates' => Crop_price::with(['crop', 'region'])
                ->latest()
                ->take(5)
                ->get()
        ]);
    }

    public function trends()
    {
        // Get price trends for the last 30 days
        $trends = Crop_price::select(
                DB::raw('DATE(date_recorded) as date'),
                DB::raw('AVG(price) as avg_price'),
                DB::raw('COUNT(DISTINCT crop_id) as crop_count')
            )
            ->whereDate('date_recorded', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get crops with most volatility
        $volatileCrops = Crop_price::select(
                'crop_id',
                DB::raw('MAX(price) - MIN(price) as price_range'),
                DB::raw('AVG(price) as avg_price')
            )
            ->with('crop')
            ->whereDate('date_recorded', '>=', now()->subDays(30))
            ->groupBy('crop_id')
            ->orderByDesc('price_range')
            ->take(5)
            ->get();

        return view('market.trends', [
            'trends' => $trends,
            'volatileCrops' => $volatileCrops,
            'dateRange' => [
                'start' => now()->subDays(30)->format('M j'),
                'end' => now()->format('M j')
            ]
        ]);
    }

    public function analysis()
    {
        // Regional price differences
        $regionalAnalysis = Region::with(['prices' => function($query) {
                $query->select('region_id', DB::raw('AVG(price) as avg_price'))
                    ->whereDate('date_recorded', '>=', now()->subDays(7))
                    ->groupBy('region_id');
            }])
            ->has('prices')
            ->get()
            ->sortByDesc(function($region) {
                return optional($region->prices->first())->avg_price;
            });

        // Crop performance analysis
        $cropPerformance = Crop::with(['prices' => function($query) {
                $query->select('crop_id', 
                    DB::raw('AVG(price) as avg_price'),
                    DB::raw('MAX(price) as max_price'),
                    DB::raw('MIN(price) as min_price')
                )
                ->whereDate('date_recorded', '>=', now()->subDays(30))
                ->groupBy('crop_id');
            }])
            ->has('prices')
            ->get()
            ->map(function($crop) {
                $priceData = $crop->prices->first();
                return [
                    'crop' => $crop,
                    'avg_price' => $priceData->avg_price ?? 0,
                    'price_range' => ($priceData->max_price ?? 0) - ($priceData->min_price ?? 0),
                    'stability' => $priceData->avg_price ? 
                        (($priceData->max_price - $priceData->min_price) / $priceData->avg_price) * 100 : 0
                ];
            })
            ->sortByDesc('stability');

        return view('market.analysis', [
            'regionalAnalysis' => $regionalAnalysis,
            'cropPerformance' => $cropPerformance,
            'timePeriod' => 'Last 30 Days'
        ]);
    }
}