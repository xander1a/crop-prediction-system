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

class CropPricePredictionService
{
    private $regressionModel;
    private $classificationModel;
    private $dataset;
    private $priceTargets;
    private $trendLabels;
    private $weatherConditions;
    private $seasonalFactors;

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
        $this->seasonalFactors = [
            1 => 0.9, 2 => 0.8, 3 => 0.7, 4 => 0.6,
            5 => 0.5, 6 => 0.4, 7 => 0.3, 8 => 0.4,
            9 => 0.5, 10 => 0.6, 11 => 0.7, 12 => 0.8
        ];
    }

    public function preparePredictionData($cropId, $regionId, $targetDate)
    {
        try {
            $historicalData = Crop_price::where('crop_id', $cropId)
                ->where('region_id', $regionId)
                ->where('date_recorded', '<=', now())
                ->orderBy('date_recorded', 'desc')
                ->limit(100)
                ->get();

            if ($historicalData->count() < 5) {
                throw new \Exception('Insufficient historical data for prediction. Need at least 5 records.');
            }

            // Use simplified approach for small datasets
            if ($historicalData->count() < 20) {
                return $this->simplePrediction($cropId, $regionId, $targetDate, $historicalData);
            }

            $this->buildDataset($historicalData, $cropId, $regionId);
            $this->trainModels();

            return $this->predict($cropId, $regionId, $targetDate);

        } catch (\Exception $e) {
            Log::error('Crop price prediction preparation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function simplePrediction($cropId, $regionId, $targetDate, $historicalData)
    {
        try {
            $targetDate = Carbon::parse($targetDate);
            $latestPrice = $historicalData->first();
            
            // Calculate simple moving averages
            $prices = $historicalData->pluck('price')->toArray();
            $recentAvg = array_sum(array_slice($prices, 0, min(5, count($prices)))) / min(5, count($prices));
            $overallAvg = array_sum($prices) / count($prices);
            
            // Calculate trend
            $trend = 'stable';
            if (count($prices) >= 2) {
                $recent = array_slice($prices, 0, 3);
                $older = array_slice($prices, -3);
                $recentAvgTrend = array_sum($recent) / count($recent);
                $olderAvgTrend = array_sum($older) / count($older);
                
                $changePercent = (($recentAvgTrend - $olderAvgTrend) / $olderAvgTrend) * 100;
                
                if ($changePercent > 5) {
                    $trend = 'increase';
                } elseif ($changePercent < -5) {
                    $trend = 'decrease';
                }
            }
            
            // Simple prediction based on seasonal factors and trend
            $seasonalFactor = $this->seasonalFactors[$targetDate->month] ?? 0.5;
            $weatherFactor = $this->mapWeatherCondition($latestPrice->wether_condition);
            
            // Base prediction on recent average with adjustments
            $predictedPrice = $recentAvg;
            
            // Apply seasonal adjustment
            if ($seasonalFactor < 0.4) {
                $predictedPrice *= 1.1; // Increase price during off-season
            } elseif ($seasonalFactor > 0.7) {
                $predictedPrice *= 0.95; // Decrease price during peak season
            }
            
            // Apply weather adjustment
            if ($weatherFactor < 0.5) {
                $predictedPrice *= 1.05; // Poor weather increases prices
            } elseif ($weatherFactor > 0.8) {
                $predictedPrice *= 0.98; // Good weather slightly decreases prices
            }
            
            // Apply trend adjustment
            if ($trend === 'increase') {
                $predictedPrice *= 1.03;
            } elseif ($trend === 'decrease') {
                $predictedPrice *= 0.97;
            }
            
            // Add some randomness to avoid identical predictions
            $randomFactor = 1 + (mt_rand(-2, 2) / 100); // ±2% random variation
            $predictedPrice *= $randomFactor;
            
            // Calculate confidence based on data quality
            $dataQuality = min(count($prices) / 20, 1); // More data = higher confidence
            $priceStability = 1 - (max($prices) - min($prices)) / $overallAvg; // Less volatility = higher confidence
            $confidenceScore = max(40, min(85, 60 + ($dataQuality * 20) + ($priceStability * 15)));
            
            // Generate insights
            $insights = $this->generateSimpleInsights($predictedPrice, $latestPrice->price, $trend, $targetDate, $seasonalFactor);
            
            // Store prediction
            $prediction = $this->storePrediction($cropId, $regionId, $predictedPrice, $confidenceScore, $targetDate);
            
            return [
                'predicted_price' => round($predictedPrice, 2),
                'current_price' => $latestPrice->price,
                'price_change' => round($predictedPrice - $latestPrice->price, 2),
                'price_change_percent' => round((($predictedPrice - $latestPrice->price) / $latestPrice->price) * 100, 2),
                'predicted_trend' => $trend,
                'confidence_score' => round($confidenceScore, 1),
                'prediction_date' => now()->toDateString(),
                'target_date' => $targetDate->toDateString(),
                'insights' => $insights,
                'prediction_id' => $prediction->id,
                'method_used' => 'simple_prediction'
            ];
            
        } catch (\Exception $e) {
            Log::error('Simple prediction failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateSimpleInsights($predictedPrice, $currentPrice, $trend, $targetDate, $seasonalFactor)
    {
        $insights = [];
        
        $priceChange = $predictedPrice - $currentPrice;
        $percentChange = ($priceChange / $currentPrice) * 100;
        
        if ($trend === 'increase') {
            $insights[] = "Price trend suggests upward movement (+{$percentChange}%)";
        } elseif ($trend === 'decrease') {
            $insights[] = "Price trend suggests downward movement ({$percentChange}%)";
        } else {
            $insights[] = "Price expected to remain stable with minor fluctuations";
        }
        
        // Seasonal insights
        if ($seasonalFactor < 0.4) {
            $insights[] = "Off-season period may contribute to higher prices";
        } elseif ($seasonalFactor > 0.7) {
            $insights[] = "Peak season may lead to increased supply and lower prices";
        }
        
        // Data quality note
        $insights[] = "Prediction based on available historical data - more data will improve accuracy";
        
        return $insights;
    }

    private function buildDataset($historicalData, $cropId, $regionId)
    {
        $this->dataset = [];
        $this->priceTargets = [];
        $this->trendLabels = [];

        $dataArray = $historicalData->toArray();
        
        // Ensure we have enough variation in features
        $minDataPoints = 5;
        if (count($dataArray) < $minDataPoints) {
            throw new \Exception('Insufficient data for complex prediction model');
        }
        
        for ($i = 0; $i < count($dataArray) - 1; $i++) {
            $current = $dataArray[$i];
            $previous = $dataArray[$i + 1];
            
            $features = $this->extractSimplifiedFeatures($current, $previous, $cropId, $regionId);
            $this->dataset[] = $features;
            
            $this->priceTargets[] = (float)$current['price'];
            
            $priceDiff = $current['price'] - $previous['price'];
            $percentChange = ($priceDiff / $previous['price']) * 100;
            
            if ($percentChange > 5) {
                $this->trendLabels[] = 'increase';
            } elseif ($percentChange < -5) {
                $this->trendLabels[] = 'decrease';
            } else {
                $this->trendLabels[] = 'stable';
            }
        }
        
        // Validate dataset
        if (empty($this->dataset)) {
            throw new \Exception('No valid data points for training');
        }
        
        // Check for feature variation
        $this->validateFeatureVariation();
    }

    private function validateFeatureVariation()
    {
        if (empty($this->dataset)) {
            throw new \Exception('Empty dataset');
        }
        
        $numFeatures = count($this->dataset[0]);
        
        for ($i = 0; $i < $numFeatures; $i++) {
            $featureValues = array_column($this->dataset, $i);
            $uniqueValues = array_unique($featureValues);
            
            // If a feature has no variation, add small random noise
            if (count($uniqueValues) === 1) {
                for ($j = 0; $j < count($this->dataset); $j++) {
                    $this->dataset[$j][$i] += mt_rand(-1, 1) * 0.001;
                }
            }
        }
    }

    private function extractSimplifiedFeatures($current, $previous, $cropId, $regionId)
    {
        $currentDate = Carbon::parse($current['date_recorded']);
        
        return [
            // Simplified temporal features
            $currentDate->month,
            $currentDate->quarter,
            
            // Price features
            (float)$previous['price'],
            
            // Weather features
            $this->mapWeatherCondition($current['wether_condition']),
            
            // Seasonal features
            $this->seasonalFactors[$currentDate->month] ?? 0.5,
            
            // Regional features
            $this->getRegionalFactor($regionId)
        ];
    }

    private function mapWeatherCondition($condition)
    {
        $condition = strtolower($condition ?? 'fair');
        return $this->weatherConditions[$condition] ?? 0.6;
    }

    private function getRegionalFactor($regionId)
    {
        return ($regionId % 5) / 10 + 0.5;
    }

    private function trainModels()
    {
        try {
            // Validate data before training
            if (empty($this->dataset) || empty($this->priceTargets)) {
                throw new \Exception('No data available for training');
            }
            
            if (count($this->dataset) < 3) {
                throw new \Exception('Need at least 3 data points for training');
            }
            
            // Train regression model
            $this->regressionModel = new LeastSquares();
            $this->regressionModel->train($this->dataset, $this->priceTargets);

            // Train classification model
            $this->classificationModel = new KNearestNeighbors(min(3, count($this->dataset)));
            $this->classificationModel->train($this->dataset, $this->trendLabels);

            Log::info('Models trained successfully with ' . count($this->dataset) . ' data points');
            
        } catch (\Exception $e) {
            Log::error('Failed to train models: ' . $e->getMessage());
            throw $e;
        }
    }

    public function predict($cropId, $regionId, $targetDate)
    {
        try {
            $targetDate = Carbon::parse($targetDate);
            
            $latestPrice = Crop_price::where('crop_id', $cropId)
                ->where('region_id', $regionId)
                ->orderBy('date_recorded', 'desc')
                ->first();

            if (!$latestPrice) {
                throw new \Exception('No historical price data found');
            }

            $features = $this->preparePredictionFeatures($latestPrice, $cropId, $regionId, $targetDate);
            
            $predictedPrice = $this->regressionModel->predict([$features])[0];
            $predictedTrend = $this->classificationModel->predict([$features])[0];
            
            $confidenceScore = $this->calculateConfidenceScore($features, $predictedPrice, $latestPrice->price);
            $insights = $this->generateInsights($predictedPrice, $latestPrice->price, $predictedTrend, $targetDate);
            
            $prediction = $this->storePrediction($cropId, $regionId, $predictedPrice, $confidenceScore, $targetDate);
            
            return [
                'predicted_price' => round($predictedPrice, 2),
                'current_price' => $latestPrice->price,
                'price_change' => round($predictedPrice - $latestPrice->price, 2),
                'price_change_percent' => round((($predictedPrice - $latestPrice->price) / $latestPrice->price) * 100, 2),
                'predicted_trend' => $predictedTrend,
                'confidence_score' => $confidenceScore,
                'prediction_date' => now()->toDateString(),
                'target_date' => $targetDate->toDateString(),
                'insights' => $insights,
                'prediction_id' => $prediction->id,
                'method_used' => 'ml_prediction'
            ];
            
        } catch (\Exception $e) {
            Log::error('Prediction failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function preparePredictionFeatures($latestPrice, $cropId, $regionId, $targetDate)
    {
        return [
            $targetDate->month,
            $targetDate->quarter,
            (float)$latestPrice->price,
            $this->mapWeatherCondition($latestPrice->wether_condition),
            $this->seasonalFactors[$targetDate->month] ?? 0.5,
            $this->getRegionalFactor($regionId)
        ];
    }

    private function calculateConfidenceScore($features, $predictedPrice, $currentPrice)
    {
        $baseConfidence = 65;
        $adjustments = 0;
        
        $seasonalFactor = $features[4] ?? 0.5;
        if ($seasonalFactor > 0.7 || $seasonalFactor < 0.3) {
            $adjustments += 10;
        }
        
        $weatherScore = $features[3] ?? 0.6;
        if ($weatherScore > 0.8) {
            $adjustments += 5;
        } elseif ($weatherScore < 0.4) {
            $adjustments -= 5;
        }
        
        $priceChange = abs($predictedPrice - $currentPrice) / $currentPrice;
        if ($priceChange > 0.3) {
            $adjustments -= 10;
        }
        
        return max(40, min(90, $baseConfidence + $adjustments));
    }

    private function generateInsights($predictedPrice, $currentPrice, $trend, $targetDate)
    {
        $insights = [];
        
        $priceChange = $predictedPrice - $currentPrice;
        $percentChange = ($priceChange / $currentPrice) * 100;
        
        if ($trend === 'increase') {
            $insights[] = "Price expected to increase by " . round($percentChange, 1) . "%";
        } elseif ($trend === 'decrease') {
            $insights[] = "Price expected to decrease by " . round(abs($percentChange), 1) . "%";
        } else {
            $insights[] = "Price expected to remain stable";
        }
        
        $month = Carbon::parse($targetDate)->month;
        if (in_array($month, [7, 8, 9])) {
            $insights[] = "Harvest season may increase supply";
        } elseif (in_array($month, [1, 2, 12])) {
            $insights[] = "Off-season period may affect supply";
        }
        
        return $insights;
    }

    private function storePrediction($cropId, $regionId, $predictedPrice, $confidenceScore, $targetDate)
    {
        return Price_prediction::create([
            'crop_id' => $cropId,
            'region_id' => $regionId,
            'predicted_price' => $predictedPrice,
            'confidence_score' => $confidenceScore,
            'prediction_date' => now(),
            'target_date' => $targetDate,
            'model_used' => 'Hybrid_v2.0'
        ]);
    }

    public function batchPredict($cropId, $regionId, $daysAhead = 30)
    {
        $predictions = [];
        $startDate = now()->addDay();
        
        for ($i = 1; $i <= $daysAhead; $i++) {
            $targetDate = $startDate->copy()->addDays($i);
            try {
                $prediction = $this->preparePredictionData($cropId, $regionId, $targetDate);
                $predictions[] = $prediction;
            } catch (\Exception $e) {
                Log::warning("Failed to predict for date {$targetDate}: " . $e->getMessage());
            }
        }
        
        return $predictions;
    }

    public function getHistoricalAccuracy($cropId, $regionId, $days = 30)
    {
        $predictions = Price_prediction::where('crop_id', $cropId)
            ->where('region_id', $regionId)
            ->where('target_date', '<=', now())
            ->where('target_date', '>=', now()->subDays($days))
            ->get();

        if ($predictions->isEmpty()) {
            return null;
        }

        $accuracySum = 0;
        $validPredictions = 0;

        foreach ($predictions as $prediction) {
            $actualPrice = Crop_price::where('crop_id', $cropId)
                ->where('region_id', $regionId)
                ->whereDate('date_recorded', $prediction->target_date)
                ->first();

            if ($actualPrice) {
                $accuracy = 100 - (abs($prediction->predicted_price - $actualPrice->price) / $actualPrice->price * 100);
                $accuracySum += max(0, $accuracy);
                $validPredictions++;
            }
        }

        return $validPredictions > 0 ? round($accuracySum / $validPredictions, 2) : null;
    }
}