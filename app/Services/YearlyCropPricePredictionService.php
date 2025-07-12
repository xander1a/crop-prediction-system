<?php

namespace App\Services;

use Phpml\Regression\LeastSquares;
use Phpml\Classification\KNearestNeighbors;
use App\Models\Crop_price;
use App\Models\Crop;
use App\Models\Region;
use App\Models\Price_prediction;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class YearlyCropPricePredictionService
{
    private $regressionModel;
    private $dataset;
    private $priceTargets;
    private $weatherConditions;
    private $seasonalFactors;
    private $yearlyTrends;

    public function __construct()
    {
        $this->initializeWeatherConditions();
        $this->initializeSeasonalFactors();
    }

    private function initializeWeatherConditions()
    {
        $this->weatherConditions = [
            'excellent' => 1.0,
            'good' => 0.8,
            'fair' => 0.6,
            'poor' => 0.4,
            'very_poor' => 0.2,
            'drought' => 0.1,
            'flood' => 0.3
        ];
    }

    private function initializeSeasonalFactors()
    {
        // Monthly factors for crop seasonality
        $this->seasonalFactors = [
            1 => 0.9, 2 => 0.8, 3 => 0.7, 4 => 0.6,
            5 => 0.5, 6 => 0.4, 7 => 0.3, 8 => 0.4,
            9 => 0.5, 10 => 0.6, 11 => 0.7, 12 => 0.8
        ];
    }

    /**
     * Main method for yearly price prediction
     */
    public function predictYearlyPrice($cropId, $regionId, $targetYear = null)
    {
        try {
            $targetYear = $targetYear ?: (date('Y') + 1);
            
            // Get 10 years of historical data
            $historicalData = $this->getHistoricalData($cropId, $regionId, 10);
            
            if ($historicalData->count() < 10) {
                throw new \Exception('Insufficient historical data. Need at least 10 years of data.');
            }

            // Prepare yearly aggregated data
            $yearlyData = $this->aggregateYearlyData($historicalData);
            
            if (count($yearlyData) < 5) {
                // Use simple trend analysis for limited data
                return $this->simpleYearlyPrediction($cropId, $regionId, $targetYear, $yearlyData);
            }

            // Build dataset and train model
            $this->buildYearlyDataset($yearlyData, $cropId, $regionId);
            $this->trainYearlyModel();

            // Generate prediction
            return $this->predictYearlyPriceML($cropId, $regionId, $targetYear, $yearlyData);

        } catch (\Exception $e) {
            Log::error('Yearly price prediction failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get historical data for specified years
     */
    private function getHistoricalData($cropId, $regionId, $years = 10)
    {
        $startDate = Carbon::now()->subYears($years)->startOfYear();
        
        return Crop_price::where('crop_id', $cropId)
            ->where('region_id', $regionId)
            ->where('date_recorded', '>=', $startDate)
            ->orderBy('date_recorded', 'asc')
            ->get();
    }

    /**
     * Aggregate data by year with comprehensive metrics
     */
    private function aggregateYearlyData($historicalData)
    {
        $yearlyData = [];
        
        foreach ($historicalData->groupBy(function($item) {
            return Carbon::parse($item->date_recorded)->year;
        }) as $year => $yearData) {
            
            $prices = $yearData->pluck('price')->toArray();
            $weatherConditions = $yearData->pluck('wether_condition')->toArray();
            
            $yearlyData[$year] = [
                'year' => $year,
                'avg_price' => array_sum($prices) / count($prices),
                'min_price' => min($prices),
                'max_price' => max($prices),
                'price_volatility' => $this->calculateVolatility($prices),
                'data_points' => count($prices),
                'weather_score' => $this->calculateYearlyWeatherScore($weatherConditions),
                'seasonal_distribution' => $this->calculateSeasonalDistribution($yearData),
                'growth_rate' => 0, // Will be calculated later
            ];
        }

        // Calculate year-over-year growth rates
        $years = array_keys($yearlyData);
        sort($years);
        
        for ($i = 1; $i < count($years); $i++) {
            $currentYear = $years[$i];
            $previousYear = $years[$i - 1];
            
            $currentPrice = $yearlyData[$currentYear]['avg_price'];
            $previousPrice = $yearlyData[$previousYear]['avg_price'];
            
            $yearlyData[$currentYear]['growth_rate'] = 
                (($currentPrice - $previousPrice) / $previousPrice) * 100;
        }

        return $yearlyData;
    }

    /**
     * Calculate price volatility (standard deviation)
     */
    private function calculateVolatility($prices)
    {
        $mean = array_sum($prices) / count($prices);
        $squareDiffs = array_map(function($price) use ($mean) {
            return pow($price - $mean, 2);
        }, $prices);
        
        return sqrt(array_sum($squareDiffs) / count($prices));
    }

    /**
     * Calculate yearly weather score
     */
    private function calculateYearlyWeatherScore($weatherConditions)
    {
        $scores = array_map(function($condition) {
            return $this->mapWeatherCondition($condition);
        }, $weatherConditions);
        
        return array_sum($scores) / count($scores);
    }

    /**
     * Calculate seasonal price distribution
     */
    private function calculateSeasonalDistribution($yearData)
    {
        $quarters = [1 => [], 2 => [], 3 => [], 4 => []];
        
        foreach ($yearData as $record) {
            $quarter = Carbon::parse($record->date_recorded)->quarter;
            $quarters[$quarter][] = $record->price;
        }
        
        $distribution = [];
        foreach ($quarters as $q => $prices) {
            $distribution["Q{$q}"] = !empty($prices) ? array_sum($prices) / count($prices) : 0;
        }
        
        return $distribution;
    }

    /**
     * Build dataset for machine learning
     */
    private function buildYearlyDataset($yearlyData, $cropId, $regionId)
    {
        $this->dataset = [];
        $this->priceTargets = [];
        
        $years = array_keys($yearlyData);
        sort($years);
        
        // Use sliding window approach for features
        for ($i = 2; $i < count($years); $i++) {
            $currentYear = $years[$i];
            $prevYear1 = $years[$i - 1];
            $prevYear2 = $years[$i - 2];
            
            $features = [
                // Historical prices (2 years back)
                $yearlyData[$prevYear2]['avg_price'],
                $yearlyData[$prevYear1]['avg_price'],
                
                // Growth rates
                $yearlyData[$prevYear1]['growth_rate'],
                $yearlyData[$prevYear2]['growth_rate'],
                
                // Weather conditions
                $yearlyData[$prevYear1]['weather_score'],
                $yearlyData[$prevYear2]['weather_score'],
                
                // Volatility indicators
                $yearlyData[$prevYear1]['price_volatility'],
                
                // Time trend (helps capture long-term patterns)
                $i, // Position in sequence
                
                // Regional factor
                $this->getRegionalFactor($regionId),
                
                // Data quality indicator
                min($yearlyData[$prevYear1]['data_points'] / 12, 1), // Normalized data points
            ];
            
            $this->dataset[] = $features;
            $this->priceTargets[] = $yearlyData[$currentYear]['avg_price'];
        }
        
        if (empty($this->dataset)) {
            throw new \Exception('Insufficient data to build yearly prediction model');
        }
    }

    /**
     * Train yearly prediction model
     */
    private function trainYearlyModel()
    {
        try {
            $this->regressionModel = new LeastSquares();
            $this->regressionModel->train($this->dataset, $this->priceTargets);
            
            Log::info('Yearly model trained successfully with ' . count($this->dataset) . ' data points');
            
        } catch (\Exception $e) {
            Log::error('Failed to train yearly model: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate ML-based yearly prediction
     */
    private function predictYearlyPriceML($cropId, $regionId, $targetYear, $yearlyData)
    {
        $years = array_keys($yearlyData);
        sort($years);
        $latestYear = end($years);
        $secondLatestYear = $years[count($years) - 2];
        
        // Prepare features for prediction
        $features = [
            $yearlyData[$secondLatestYear]['avg_price'],
            $yearlyData[$latestYear]['avg_price'],
            $yearlyData[$latestYear]['growth_rate'],
            $yearlyData[$secondLatestYear]['growth_rate'],
            $yearlyData[$latestYear]['weather_score'],
            $yearlyData[$secondLatestYear]['weather_score'],
            $yearlyData[$latestYear]['price_volatility'],
            count($years) + 1, // Next position in sequence
            $this->getRegionalFactor($regionId),
            min($yearlyData[$latestYear]['data_points'] / 12, 1),
        ];
        
        $predictedPrice = $this->regressionModel->predict([$features])[0];
        
        // Calculate confidence score
        $confidenceScore = $this->calculateYearlyConfidence($yearlyData, $predictedPrice, $latestYear);
        
        // Generate insights
        $insights = $this->generateYearlyInsights($predictedPrice, $yearlyData, $targetYear);
        
        // Store prediction
        $prediction = $this->storeYearlyPrediction($cropId, $regionId, $predictedPrice, $confidenceScore, $targetYear);
        
        return [
            'predicted_price' => round($predictedPrice, 2),
            'current_avg_price' => round($yearlyData[$latestYear]['avg_price'], 2),
            'price_change' => round($predictedPrice - $yearlyData[$latestYear]['avg_price'], 2),
            'price_change_percent' => round((($predictedPrice - $yearlyData[$latestYear]['avg_price']) / $yearlyData[$latestYear]['avg_price']) * 100, 2),
            'confidence_score' => round($confidenceScore, 1),
            'target_year' => $targetYear,
            'historical_avg_growth' => round($this->calculateAverageGrowthRate($yearlyData), 2),
            'insights' => $insights,
            'prediction_id' => $prediction->id,
            'method_used' => 'yearly_ml_prediction',
            'years_of_data' => count($yearlyData)
        ];
    }

    /**
     * Simple yearly prediction for limited data
     */
    private function simpleYearlyPrediction($cropId, $regionId, $targetYear, $yearlyData)
    {
        $years = array_keys($yearlyData);
        sort($years);
        
        // Calculate trend
        $avgGrowthRate = $this->calculateAverageGrowthRate($yearlyData);
        $latestPrice = $yearlyData[end($years)]['avg_price'];
        
        // Apply trend to predict next year
        $predictedPrice = $latestPrice * (1 + ($avgGrowthRate / 100));
        
        // Adjust for volatility and data quality
        $avgVolatility = array_sum(array_column($yearlyData, 'price_volatility')) / count($yearlyData);
        $volatilityFactor = 1 + (mt_rand(-5, 5) / 100); // ±5% random factor based on volatility
        
        $predictedPrice *= $volatilityFactor;
        
        $confidenceScore = max(50, min(80, 65 - ($avgVolatility * 10) + (count($yearlyData) * 2)));
        
        $insights = [
            "Based on {count($yearlyData)} years of data",
            "Average annual growth rate: " . round($avgGrowthRate, 2) . "%",
            "Historical price volatility considered",
            "Limited data - consider collecting more historical records"
        ];
        
        $prediction = $this->storeYearlyPrediction($cropId, $regionId, $predictedPrice, $confidenceScore, $targetYear);
        
        return [
            'predicted_price' => round($predictedPrice, 2),
            'current_avg_price' => round($latestPrice, 2),
            'price_change' => round($predictedPrice - $latestPrice, 2),
            'price_change_percent' => round((($predictedPrice - $latestPrice) / $latestPrice) * 100, 2),
            'confidence_score' => round($confidenceScore, 1),
            'target_year' => $targetYear,
            'historical_avg_growth' => round($avgGrowthRate, 2),
            'insights' => $insights,
            'prediction_id' => $prediction->id,
            'method_used' => 'simple_yearly_prediction',
            'years_of_data' => count($yearlyData)
        ];
    }

    /**
     * Calculate average growth rate
     */
    private function calculateAverageGrowthRate($yearlyData)
    {
        $growthRates = array_filter(array_column($yearlyData, 'growth_rate'), function($rate) {
            return $rate !== 0;
        });
        
        return !empty($growthRates) ? array_sum($growthRates) / count($growthRates) : 0;
    }

    /**
     * Calculate confidence score for yearly prediction
     */
    private function calculateYearlyConfidence($yearlyData, $predictedPrice, $latestYear)
    {
        $baseConfidence = 70;
        
        // Data quality factor
        $dataQuality = min(count($yearlyData) / 10, 1) * 15;
        
        // Volatility factor (lower volatility = higher confidence)
        $avgVolatility = array_sum(array_column($yearlyData, 'price_volatility')) / count($yearlyData);
        $avgPrice = array_sum(array_column($yearlyData, 'avg_price')) / count($yearlyData);
        $volatilityRatio = $avgVolatility / $avgPrice;
        $volatilityPenalty = min($volatilityRatio * 50, 20);
        
        // Trend consistency factor
        $growthRates = array_filter(array_column($yearlyData, 'growth_rate'));
        $trendConsistency = 1 - (max($growthRates) - min($growthRates)) / 100;
        $trendBonus = max(0, $trendConsistency * 10);
        
        return max(40, min(95, $baseConfidence + $dataQuality - $volatilityPenalty + $trendBonus));
    }

    /**
     * Generate yearly insights
     */
    private function generateYearlyInsights($predictedPrice, $yearlyData, $targetYear)
    {
        $insights = [];
        $years = array_keys($yearlyData);
        sort($years);
        $latestYear = end($years);
        $latestPrice = $yearlyData[$latestYear]['avg_price'];
        
        $changePercent = (($predictedPrice - $latestPrice) / $latestPrice) * 100;
        
        if ($changePercent > 10) {
            $insights[] = "Significant price increase expected (+{round($changePercent, 1)}%)";
        } elseif ($changePercent < -10) {
            $insights[] = "Significant price decrease expected ({round($changePercent, 1)}%)";
        } else {
            $insights[] = "Price expected to remain relatively stable ({round($changePercent, 1)}% change)";
        }
        
        $avgGrowth = $this->calculateAverageGrowthRate($yearlyData);
        $insights[] = "Historical average growth: " . round($avgGrowth, 1) . "% per year";
        
        $volatility = array_sum(array_column($yearlyData, 'price_volatility')) / count($yearlyData);
        if ($volatility > $latestPrice * 0.2) {
            $insights[] = "High price volatility observed - expect significant fluctuations";
        }
        
        $insights[] = "Based on " . count($yearlyData) . " years of historical data";
        
        return $insights;
    }

    /**
     * Store yearly prediction
     */
    private function storeYearlyPrediction($cropId, $regionId, $predictedPrice, $confidenceScore, $targetYear)
    {
        return Price_prediction::create([
            'crop_id' => $cropId,
            'region_id' => $regionId,
            'predicted_price' => $predictedPrice,
            'confidence_score' => $confidenceScore,
            'prediction_date' => now(),
            'target_date' => Carbon::createFromDate($targetYear, 6, 15), // Mid-year target
            'model_used' => 'Yearly_Prediction_v1.0'
        ]);
    }

    // Helper methods (kept from original)
    private function mapWeatherCondition($condition)
    {
        $condition = strtolower($condition ?? 'fair');
        return $this->weatherConditions[$condition] ?? 0.6;
    }

    private function getRegionalFactor($regionId)
    {
        return ($regionId % 5) / 10 + 0.5;
    }
}