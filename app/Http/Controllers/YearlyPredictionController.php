<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\YearlyCropPricePredictionService;
use App\Services\CropPricePredictionService; // Keep original for short-term predictions
use App\Models\Price_prediction;
use App\Models\Crop;
use App\Models\Region;
use App\Models\Crop_price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class YearlyPredictionController extends Controller
{
    protected $yearlyPredictionService;
    protected $shortTermPredictionService;

    public function __construct(
        YearlyCropPricePredictionService $yearlyPredictionService,
        CropPricePredictionService $shortTermPredictionService
    ) {
        $this->yearlyPredictionService = $yearlyPredictionService;
        $this->shortTermPredictionService = $shortTermPredictionService;
    }

    /**
     * Show yearly prediction form
     */
    public function createYearly()
    {
        try {
            $crops = Crop::orderBy('name')->get();
            $regions = Region::orderBy('name')->get();

            // Get data availability for yearly predictions (10 years)
            $dataAvailability = [];
            foreach ($crops as $crop) {
                foreach ($regions as $region) {
                    $yearsOfData = $this->getYearsOfData($crop->id, $region->id);
                    
                    if ($yearsOfData >= 3) { // Show only combinations with at least 3 years
                        $dataAvailability[] = [
                            'crop_id' => $crop->id,
                            'crop_name' => $crop->name,
                            'region_id' => $region->id,
                            'region_name' => $region->name,
                            'years_of_data' => $yearsOfData,
                            'suitable_for_prediction' => $yearsOfData >= 5
                        ];
                    }
                }
            }

            return view('predictions.create_yearly', compact(
                'crops', 
                'regions', 
                'dataAvailability'
            ));

        } catch (Exception $e) {
            Log::error('Error loading yearly prediction page: ' . $e->getMessage());
            return redirect()->route('predictions.index')
                ->with('error', 'Unable to load yearly prediction page: ' . $e->getMessage());
        }
    }

    /**
     * Generate yearly prediction
     */
    public function generateYearlyPrediction(Request $request)
    {
        $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'region_id' => 'required|exists:regions,id',
            'target_year' => 'nullable|integer|min:' . (date('Y') + 1) . '|max:' . (date('Y') + 5),
        ]);

        try {
            $targetYear = $request->target_year ?: (date('Y') + 1);
            
            // Check data availability
            $yearsOfData = $this->getYearsOfData($request->crop_id, $request->region_id);
            
            if ($yearsOfData < 3) {
                return back()->withInput()
                    ->with('error', "Insufficient data for yearly prediction. Found {$yearsOfData} years of data, need at least 3 years.");
            }

            // Generate prediction
            $prediction = $this->yearlyPredictionService->predictYearlyPrice(
                $request->crop_id,
                $request->region_id,
                $targetYear
            );

            // Get additional context
            $crop = Crop::find($request->crop_id);
            $region = Region::find($request->region_id);
            $historicalSummary = $this->getHistoricalSummary($request->crop_id, $request->region_id);

            Log::info('Yearly prediction generated successfully:', [
                'crop' => $crop->name,
                'region' => $region->name,
                'target_year' => $targetYear,
                'predicted_price' => $prediction['predicted_price']
            ]);

            return view('predictions.yearly_result', compact(
                'prediction',
                'crop',
                'region',
                'historicalSummary',
                'targetYear'
            ));

        } catch (Exception $e) {
            Log::error('Error generating yearly prediction: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Unable to generate yearly prediction: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint for yearly prediction
     */
    public function apiYearlyPredict(Request $request)
    {
        $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'region_id' => 'required|exists:regions,id',
            'target_year' => 'nullable|integer|min:' . (date('Y') + 1) . '|max:' . (date('Y') + 5),
        ]);

        try {
            $targetYear = $request->target_year ?: (date('Y') + 1);
            
            $prediction = $this->yearlyPredictionService->predictYearlyPrice(
                $request->crop_id,
                $request->region_id,
                $targetYear
            );

            return response()->json([
                'success' => true,
                'data' => $prediction,
                'message' => 'Yearly prediction generated successfully'
            ]);

        } catch (Exception $e) {
            Log::error('API yearly prediction error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get data availability for a crop/region combination
     */
    public function checkDataAvailability(Request $request)
    {
        $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'region_id' => 'required|exists:regions,id',
        ]);

        try {
            $yearsOfData = $this->getYearsOfData($request->crop_id, $request->region_id);
            $historicalSummary = $this->getHistoricalSummary($request->crop_id, $request->region_id);

            return response()->json([
                'success' => true,
                'years_of_data' => $yearsOfData,
                'suitable_for_prediction' => $yearsOfData >= 5,
                'minimum_required' => 3,
                'historical_summary' => $historicalSummary
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Compare predictions across multiple years
     */
    public function compareYearlyPredictions(Request $request)
    {
        $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'region_id' => 'required|exists:regions,id',
            'years_ahead' => 'nullable|integer|min:1|max:5',
        ]);

        try {
            $yearsAhead = $request->years_ahead ?: 3;
            $predictions = [];
            $currentYear = date('Y');

            for ($i = 1; $i <= $yearsAhead; $i++) {
                $targetYear = $currentYear + $i;
                
                try {
                    $prediction = $this->yearlyPredictionService->predictYearlyPrice(
                        $request->crop_id,
                        $request->region_id,
                        $targetYear
                    );
                    $predictions[] = $prediction;
                } catch (Exception $e) {
                    Log::warning("Failed to predict for year {$targetYear}: " . $e->getMessage());
                }
            }

            $crop = Crop::find($request->crop_id);
            $region = Region::find($request->region_id);

            return response()->json([
                'success' => true,
                'predictions' => $predictions,
                'crop' => $crop->name,
                'region' => $region->name,
                'comparison_period' => "{$currentYear}-" . ($currentYear + $yearsAhead)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display all yearly predictions with filters
     */
    public function index(Request $request)
    {

      
        try {
            $query = Price_prediction::with(['crop', 'region'])
                ->where('model_used', 'like', '%Yearly%')
                ->orderBy('prediction_date', 'desc');

            // Apply filters
            if ($request->filled('crop_id')) {
                $query->where('crop_id', $request->crop_id);
            }

            if ($request->filled('region_id')) {
                $query->where('region_id', $request->region_id);
            }

            if ($request->filled('target_year')) {
                $query->whereYear('target_date', $request->target_year);
            }

            $predictions = $query->paginate(15);
            $crops = Crop::orderBy('name')->get();
            $regions = Region::orderBy('name')->get();

dd($query);

            return view('predictions.yearly_index', compact(
                'predictions',
                'crops',
                'regions'
            ));

        } catch (Exception $e) {
            Log::error('Error loading yearly predictions index: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Unable to load predictions: ' . $e->getMessage());
        }
    }

    /**
     * Show specific yearly prediction details
     */
    public function show($id)
    {
        try {
            $prediction = Price_prediction::with(['crop', 'region'])
                ->where('model_used', 'like', '%Yearly%')
                ->findOrFail($id);

            // Get historical context
            $historicalSummary = $this->getHistoricalSummary(
                $prediction->crop_id,
                $prediction->region_id
            );

            // Get recent predictions for comparison
            $recentPredictions = Price_prediction::where('crop_id', $prediction->crop_id)
                ->where('region_id', $prediction->region_id)
                ->where('model_used', 'like', '%Yearly%')
                ->where('id', '!=', $id)
                ->orderBy('prediction_date', 'desc')
                ->limit(5)
                ->get();

            return view('predictions.yearly_show', compact(
                'prediction',
                'historicalSummary',
                'recentPredictions'
            ));

        } catch (Exception $e) {
            Log::error('Error showing yearly prediction: ' . $e->getMessage());
            return redirect()->route('predictions.yearly.index')
                ->with('error', 'Prediction not found or error occurred.');
        }
    }

    /**
     * Delete a yearly prediction
     */
    public function destroy($id)
    {
        try {
            $prediction = Price_prediction::where('model_used', 'like', '%Yearly%')
                ->findOrFail($id);

            $prediction->delete();

            return redirect()->route('predictions.yearly.index')
                ->with('success', 'Yearly prediction deleted successfully.');

        } catch (Exception $e) {
            Log::error('Error deleting yearly prediction: ' . $e->getMessage());
            return redirect()->route('predictions.yearly.index')
                ->with('error', 'Unable to delete prediction.');
        }
    }

    /**
     * Export yearly predictions to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = Price_prediction::with(['crop', 'region'])
                ->where('model_used', 'like', '%Yearly%');

            // Apply same filters as index
            if ($request->filled('crop_id')) {
                $query->where('crop_id', $request->crop_id);
            }

            if ($request->filled('region_id')) {
                $query->where('region_id', $request->region_id);
            }

            if ($request->filled('target_year')) {
                $query->whereYear('target_date', $request->target_year);
            }

            $predictions = $query->orderBy('prediction_date', 'desc')->get();

            $filename = 'yearly_predictions_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function() use ($predictions) {
                $file = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($file, [
                    'ID',
                    'Crop',
                    'Region',
                    'Predicted Price',
                    'Confidence Score',
                    'Prediction Date',
                    'Target Date',
                    'Model Used'
                ]);

                // CSV Data
                foreach ($predictions as $prediction) {
                    fputcsv($file, [
                        $prediction->id,
                        $prediction->crop->name,
                        $prediction->region->name,
                        $prediction->predicted_price,
                        $prediction->confidence_score,
                        $prediction->prediction_date,
                        $prediction->target_date,
                        $prediction->model_used
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (Exception $e) {
            Log::error('Error exporting yearly predictions: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Unable to export predictions.');
        }
    }

    /**
     * Helper: Get years of available data
     */
    private function getYearsOfData($cropId, $regionId)
    {
        $years = Crop_price::where('crop_id', $cropId)
            ->where('region_id', $regionId)
            ->selectRaw('YEAR(date_recorded) as year')
            ->groupBy('year')
            ->get()
            ->pluck('year')
            ->count();

        return $years;
    }

    /**
     * Helper: Get historical summary
     */
    private function getHistoricalSummary($cropId, $regionId)
    {
        $tenYearsAgo = Carbon::now()->subYears(10);
        
        $data = Crop_price::where('crop_id', $cropId)
            ->where('region_id', $regionId)
            ->where('date_recorded', '>=', $tenYearsAgo)
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $prices = $data->pluck('price')->toArray();
        $yearlyAvgs = $data->groupBy(function($item) {
            return Carbon::parse($item->date_recorded)->year;
        })->map(function($yearData) {
            return $yearData->avg('price');
        });

        // Calculate year-over-year growth
        $years = $yearlyAvgs->keys()->sort()->values();
        $growthRates = [];
        
        for ($i = 1; $i < $years->count(); $i++) {
            $currentYear = $years[$i];
            $prevYear = $years[$i - 1];
            $growth = (($yearlyAvgs[$currentYear] - $yearlyAvgs[$prevYear]) / $yearlyAvgs[$prevYear]) * 100;
            $growthRates[] = $growth;
        }

        return [
            'total_records' => $data->count(),
            'years_covered' => $years->count(),
            'date_range' => [
                'from' => $data->min('date_recorded'),
                'to' => $data->max('date_recorded')
            ],
            'price_range' => [
                'min' => min($prices),
                'max' => max($prices),
                'avg' => round(array_sum($prices) / count($prices), 2)
            ],
            'yearly_averages' => $yearlyAvgs->toArray(),
            'average_annual_growth' => !empty($growthRates) ? round(array_sum($growthRates) / count($growthRates), 2) : 0,
            'recent_trend' => $this->getRecentTrend($yearlyAvgs),
            'weather_analysis' => $this->getWeatherAnalysis($data)
        ];
    }

    /**
     * Helper: Get recent trend analysis
     */
    private function getRecentTrend($yearlyAvgs)
    {
        if ($yearlyAvgs->count() < 3) {
            return 'insufficient_data';
        }

        $recentYears = $yearlyAvgs->keys()->sort()->slice(-3)->values();
        $recentPrices = $recentYears->map(function($year) use ($yearlyAvgs) {
            return $yearlyAvgs[$year];
        });

        $firstPrice = $recentPrices->first();
        $lastPrice = $recentPrices->last();

        if ($lastPrice > $firstPrice * 1.1) {
            return 'increasing';
        } elseif ($lastPrice < $firstPrice * 0.9) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Helper: Get weather condition analysis
     */
    private function getWeatherAnalysis($data)
    {
        $weatherCounts = $data->groupBy('wether_condition')
            ->map(function($group) {
                return $group->count();
            });

        $totalRecords = $data->count();
        $weatherPercentages = $weatherCounts->map(function($count) use ($totalRecords) {
            return round(($count / $totalRecords) * 100, 1);
        });

        return [
            'conditions' => $weatherPercentages->toArray(),
            'dominant_condition' => $weatherPercentages->keys()->first(),
            'favorable_percentage' => $weatherPercentages->filter(function($percentage, $condition) {
                return in_array(strtolower($condition), ['excellent', 'good', 'fair']);
            })->sum()
        ];
    }
}