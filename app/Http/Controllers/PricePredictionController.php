<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Crop_price;
use App\Models\Price_prediction;
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\ModelManager;
use Phpml\Metric\Regression;
use Carbon\Carbon;

class CropPricePredictionController extends Controller
{
    private $weatherMap = [
        'excellent' => 6,
        'good' => 5,
        'fair' => 4,
        'poor' => 3,
        'very_poor' => 2,
        'drought' => 1,
        'flood' => 0,
    ];

    public function trainModel($cropId)
    {
        try {
            $recordCount = Crop_price::where('crop_id', $cropId)->count();
            if ($recordCount < 5) {
                return back()->withErrors(['error' => 'Not enough data to train the model. At least 5 years of price data required.']);
            }

            $records = Crop_price::where('crop_id', $cropId)->get();
            $samples = [];
            $labels = [];
            $csvData = [];

            foreach ($records as $record) {
                $year = (int) date('Y', strtotime($record->date_recorded));
                $weatherText = $record->wether_condition ?? 'poor';
                $weather = $this->weatherMap[$weatherText] ?? 3;
                
                $samples[] = [$year, $weather];
                $labels[] = (float) $record->price;
                $csvData[] = [$year, $weatherText, $record->price];
            }

            // Split data for training and testing (80-20 split)
            $totalSamples = count($samples);
            $trainSize = (int) ($totalSamples * 0.8);
            
            $trainSamples = array_slice($samples, 0, $trainSize);
            $trainLabels = array_slice($labels, 0, $trainSize);
            $testSamples = array_slice($samples, $trainSize);
            $testLabels = array_slice($labels, $trainSize);

            // Train the model
            $regression = new SVR(Kernel::LINEAR, 1000);
            $regression->train($trainSamples, $trainLabels);

            // Calculate accuracy metrics if we have test data
            $accuracy = null;
            $mse = null;
            $mae = null;
            $r2 = null;

            if (count($testSamples) > 0) {
                $predictions = [];
                foreach ($testSamples as $testSample) {
                    $predictions[] = $regression->predict($testSample);
                }

                // Calculate metrics
                $mse = $this->calculateMSE($testLabels, $predictions);
                $mae = $this->calculateMAE($testLabels, $predictions);
                $r2 = $this->calculateR2($testLabels, $predictions);
                
                // Convert R² to percentage for easier understanding
                $accuracy = max(0, $r2 * 100); // Ensure non-negative
            }

            // Save model
            $modelPath = storage_path("app/models/crop_{$cropId}_svr.model");
            if (!file_exists(dirname($modelPath))) {
                mkdir(dirname($modelPath), 0777, true);
            }
            (new ModelManager())->saveToFile($regression, $modelPath);

            // Save accuracy metrics to file
            $metricsPath = storage_path("app/models/crop_{$cropId}_metrics.json");
            $metrics = [
                'accuracy_percentage' => round($accuracy ?? 0, 2),
                'r_squared' => round($r2 ?? 0, 4),
                'mean_squared_error' => round($mse ?? 0, 2),
                'mean_absolute_error' => round($mae ?? 0, 2),
                'training_samples' => count($trainSamples),
                'test_samples' => count($testSamples),
                'trained_at' => now()->toISOString()
            ];
            file_put_contents($metricsPath, json_encode($metrics, JSON_PRETTY_PRINT));

            // Save CSV
            $csvPath = storage_path("app/models/crop_{$cropId}_training_data.csv");
            $csvFile = fopen($csvPath, 'w');
            fputcsv($csvFile, ['Year', 'Weather', 'Price']);
            foreach ($csvData as $row) {
                fputcsv($csvFile, $row);
            }
            fclose($csvFile);

            $message = 'Model trained and saved successfully.';
            if ($accuracy !== null) {
                $message .= " Model accuracy: " . round($accuracy, 1) . "%";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Training failed: ' . $e->getMessage()]);
        }
    }

    public function predictAndSave(Request $request, $cropId)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
            'target_year' => 'required|integer|min:2024',
            'weather_condition' => 'required|in:excellent,good,fair,poor,very_poor,drought,flood',
        ]);

        $weatherValue = $this->weatherMap[$request->weather_condition];
        $modelPath = storage_path("app/models/crop_{$cropId}_svr.model");
        $metricsPath = storage_path("app/models/crop_{$cropId}_metrics.json");

        if (!file_exists($modelPath)) {
            return back()->with('error', 'Model not found. Please train it first.');
        }

        // Load model accuracy if available
        $confidence = null;
        if (file_exists($metricsPath)) {
            $metrics = json_decode(file_get_contents($metricsPath), true);
            $confidence = $metrics['accuracy_percentage'] ?? null;
        }

        $modelManager = new ModelManager();
        $regression = $modelManager->restoreFromFile($modelPath);
        $predictedPrice = $regression->predict([(int)$request->target_year, $weatherValue]);

        $prediction = Price_prediction::create([
            'crop_id' => $cropId,
            'region_id' => $request->region_id,
            'predicted_price' => round($predictedPrice, 2),
            'confidence_score' => $confidence,
            'accuracy' => $confidence, // Store accuracy percentage
            'prediction_date' => now(),
            'target_date' => Carbon::createFromDate($request->target_year, 1, 1)->toDateString(),
            'model_used' => 'SVR',
        ]);

        $message = 'Prediction saved: ' . $prediction->predicted_price;
        if ($confidence !== null) {
            $message .= ' (Model accuracy: ' . round($confidence, 1) . '%)';
        }

        return back()->with('success', $message);
    }

    // Add method to get model accuracy
    public function getModelAccuracy($cropId)
    {
        $metricsPath = storage_path("app/models/crop_{$cropId}_metrics.json");
        
        if (!file_exists($metricsPath)) {
            return response()->json(['error' => 'No accuracy data available'], 404);
        }

        $metrics = json_decode(file_get_contents($metricsPath), true);
        return response()->json($metrics);
    }

    // Helper methods for calculating accuracy metrics
    private function calculateMSE($actual, $predicted)
    {
        $sum = 0;
        $n = count($actual);
        
        for ($i = 0; $i < $n; $i++) {
            $sum += pow($actual[$i] - $predicted[$i], 2);
        }
        
        return $sum / $n;
    }

    private function calculateMAE($actual, $predicted)
    {
        $sum = 0;
        $n = count($actual);
        
        for ($i = 0; $i < $n; $i++) {
            $sum += abs($actual[$i] - $predicted[$i]);
        }
        
        return $sum / $n;
    }

    private function calculateR2($actual, $predicted)
    {
        $actualMean = array_sum($actual) / count($actual);
        
        $ssTotal = 0;
        $ssRes = 0;
        
        for ($i = 0; $i < count($actual); $i++) {
            $ssTotal += pow($actual[$i] - $actualMean, 2);
            $ssRes += pow($actual[$i] - $predicted[$i], 2);
        }
        
        if ($ssTotal == 0) return 0;
        
        return 1 - ($ssRes / $ssTotal);
    }

    // Method to validate existing predictions against actual prices
    public function validatePredictions($cropId)
    {
        try {
            // Get predictions that should have actual data by now
            $predictions = Price_prediction::where('crop_id', $cropId)
                ->where('target_date', '<=', now())
                ->get();

            $validationResults = [];
            $totalError = 0;
            $validPredictions = 0;

            foreach ($predictions as $prediction) {
                $targetYear = date('Y', strtotime($prediction->target_date));
                
                // Look for actual price data for that year
                $actualRecord = Crop_price::where('crop_id', $cropId)
                    ->whereYear('date_recorded', $targetYear)
                    ->first();

                if ($actualRecord) {
                    $actualPrice = $actualRecord->price;
                    $predictedPrice = $prediction->predicted_price;
                    $error = abs($actualPrice - $predictedPrice);
                    $errorPercentage = ($error / $actualPrice) * 100;

                    $validationResults[] = [
                        'prediction_id' => $prediction->id,
                        'target_year' => $targetYear,
                        'predicted_price' => $predictedPrice,
                        'actual_price' => $actualPrice,
                        'error' => round($error, 2),
                        'error_percentage' => round($errorPercentage, 2)
                    ];

                    $totalError += $errorPercentage;
                    $validPredictions++;
                }
            }

            $averageAccuracy = $validPredictions > 0 ? 
                100 - ($totalError / $validPredictions) : 0;

            return response()->json([
                'average_accuracy' => round(max(0, $averageAccuracy), 2),
                'validated_predictions' => $validPredictions,
                'details' => $validationResults
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}