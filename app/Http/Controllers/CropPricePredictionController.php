<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Price_prediction;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CropPricePredictionController extends Controller
{
    private $pythonPath;
    private $scriptsPath;

    public function __construct()
    {
        $this->pythonPath = config('app.python_path', 'python3');
        $this->scriptsPath = base_path('python');
    }

    /**
     * Train model using Python script and CSV dataset
     */
    public function trainModel(Request $request)
    {
        $request->validate([
            'commodity_id' => 'required|integer',
            'dataset_file' => 'required|string', // Path to CSV file
        ]);

        try {
            $commodityId = $request->commodity_id;
            $datasetPath = $request->dataset_file;

            // Verify dataset file exists
            if (!file_exists($datasetPath)) {
                return back()->withErrors(['error' => 'Dataset file not found: ' . $datasetPath]);
            }

            // Create models directory if it doesn't exist
            $modelsDir = storage_path('app/models');
            if (!file_exists($modelsDir)) {
                mkdir($modelsDir, 0777, true);
            }

            // Prepare Python command
            $process = new Process([
                $this->pythonPath,
                $this->scriptsPath . '/train_crop_model.py',
                '--dataset', $datasetPath,
                '--commodity-id', $commodityId,
                '--output', $modelsDir,
                '--test-size', '0.2'
            ]);

            $process->setTimeout(1800); // 30 minutes timeout
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('Python training failed: ' . $process->getErrorOutput());
                return back()->withErrors(['error' => 'Model training failed: ' . $process->getErrorOutput()]);
            }

            // Parse Python output to get results
            $output = $process->getOutput();
            $result = $this->parseTrainingOutput($output);

            if ($result && $result['success']) {
                $message = "Model trained successfully! ";
                $message .= "Model: {$result['model_name']}, ";
                $message .= "Accuracy: {$result['accuracy']}%";
                
                return back()->with('success', $message);
            } else {
                return back()->withErrors(['error' => 'Training completed but results unclear']);
            }

        } catch (ProcessFailedException $e) {
            Log::error('Process failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Training process failed: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Training error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Training failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Make prediction using trained Python model
     */
    public function predictAndSave(Request $request, $cropId)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
            'target_year' => 'required|integer|min:2024',
            'target_month' => 'required|integer|min:1|max:12',
            'market_id' => 'required|integer',
            'commodity_id' => 'required|integer',
            'admin1' => 'string',
            'admin2' => 'string',
            'market_name' => 'string',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'recent_price' => 'numeric',
        ]);

        try {
            $commodityId = $request->commodity_id;
            $modelsDir = storage_path('app/models');
            
            // Check if model exists
            $modelPath = $modelsDir . "/crop_{$commodityId}_*.pkl";
            $modelFiles = glob($modelPath);
            
            if (empty($modelFiles)) {
                return back()->withErrors(['error' => 'Model not found. Please train the model first.']);
            }

            $modelFile = $modelFiles[0]; // Use first matching model
            $metadataFile = $modelsDir . "/crop_{$commodityId}_metadata.pkl";

            if (!file_exists($metadataFile)) {
                return back()->withErrors(['error' => 'Model metadata not found.']);
            }

            // Prepare input data for Python script
            $targetDate = Carbon::createFromDate($request->target_year, $request->target_month, 1);
            
            $inputData = [
                'commodity_id' => $commodityId,
                'target_date' => $targetDate->format('Y-m-d'),
                'market_id' => $request->market_id,
                'admin1' => $request->admin1 ?? 'Kigali City',
                'admin2' => $request->admin2 ?? 'Nyarugenge',
                'market' => $request->market_name ?? 'Kigali',
                'latitude' => $request->latitude ?? -1.95,
                'longitude' => $request->longitude ?? 30.06,
                'category' => 'cereals and tubers', // Default or get from commodity
                'commodity' => 'Maize', // Default or get from commodity table
                'currency' => 'RWF',
                'pricetype' => 'Wholesale',
                'recent_price' => $request->recent_price ?? 200.0,
                'price_3_months_ago' => $request->recent_price ?? 200.0,
                'price_avg_3_months' => $request->recent_price ?? 200.0,
                'price_avg_6_months' => $request->recent_price ?? 200.0,
            ];

            // Save input data to temporary file
            $inputFile = storage_path('temp/prediction_input_' . uniqid() . '.json');
            if (!file_exists(dirname($inputFile))) {
                mkdir(dirname($inputFile), 0777, true);
            }
            file_put_contents($inputFile, json_encode($inputData));

            // Run Python prediction script
            $process = new Process([
                $this->pythonPath,
                $this->scriptsPath . '/predict_crop_price.py',
                '--model', $modelFile,
                '--metadata', $metadataFile,
                '--input', $inputFile
            ]);

            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            // Clean up temp file
            if (file_exists($inputFile)) {
                unlink($inputFile);
            }

            if (!$process->isSuccessful()) {
                Log::error('Python prediction failed: ' . $process->getErrorOutput());
                return back()->withErrors(['error' => 'Prediction failed: ' . $process->getErrorOutput()]);
            }

            // Parse prediction output
            $output = $process->getOutput();
            $result = $this->parsePredictionOutput($output);

            if (!$result || !$result['success']) {
                return back()->withErrors(['error' => 'Prediction failed or returned invalid results']);
            }

            // Save prediction to database
            $prediction = Price_prediction::create([
                'crop_id' => $cropId,
                'region_id' => $request->region_id,
                'predicted_price' => $result['predicted_price'],
                'confidence_score' => $result['confidence_score'],
                'accuracy' => $result['confidence_score'],
                'prediction_date' => now(),
                'target_date' => $targetDate->toDateString(),
                'model_used' => $result['model_used'],
            ]);

            $message = "Prediction saved: {$prediction->predicted_price} RWF";
            if ($result['confidence_score']) {
                $message .= " (Model accuracy: {$result['confidence_score']}%)";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Prediction error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Prediction failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get model accuracy and metrics
     */
    public function getModelAccuracy($cropId)
    {
        $metricsPath = storage_path("app/models/crop_{$cropId}_metrics.json");
        
        if (!file_exists($metricsPath)) {
            return response()->json(['error' => 'No accuracy data available'], 404);
        }

        $metrics = json_decode(file_get_contents($metricsPath), true);
        return response()->json($metrics);
    }

    /**
     * Get available trained models
     */
    public function getAvailableModels()
    {
        $modelsDir = storage_path('app/models');
        $models = [];

        if (!is_dir($modelsDir)) {
            return response()->json(['models' => []]);
        }

        $files = glob($modelsDir . '/crop_*_metrics.json');
        
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/crop_(\d+|all)_metrics\.json/', $filename, $matches)) {
                $commodityId = $matches[1];
                $metrics = json_decode(file_get_contents($file), true);
                
                $models[] = [
                    'commodity_id' => $commodityId,
                    'model_type' => $metrics['model_type'] ?? 'unknown',
                    'accuracy' => $metrics['accuracy_percentage'] ?? 0,
                    'trained_at' => $metrics['trained_at'] ?? null,
                    'training_samples' => $metrics['training_samples'] ?? 0,
                    'test_samples' => $metrics['test_samples'] ?? 0,
                ];
            }
        }

        return response()->json(['models' => $models]);
    }

    /**
     * Batch predict for multiple scenarios
     */
    public function batchPredict(Request $request)
    {
        $request->validate([
            'commodity_id' => 'required|integer',
            'scenarios' => 'required|array',
            'scenarios.*.target_date' => 'required|date',
            'scenarios.*.market_id' => 'required|integer',
        ]);

        try {
            $commodityId = $request->commodity_id;
            $scenarios = $request->scenarios;
            $results = [];

            // Check if model exists
            $modelsDir = storage_path('app/models');
            $modelPath = $modelsDir . "/crop_{$commodityId}_*.pkl";
            $modelFiles = glob($modelPath);
            
            if (empty($modelFiles)) {
                return response()->json(['error' => 'Model not found'], 404);
            }

            $modelFile = $modelFiles[0];
            $metadataFile = $modelsDir . "/crop_{$commodityId}_metadata.pkl";

            foreach ($scenarios as $index => $scenario) {
                try {
                    // Prepare input data
                    $inputData = [
                        'commodity_id' => $commodityId,
                        'target_date' => $scenario['target_date'],
                        'market_id' => $scenario['market_id'],
                        'admin1' => $scenario['admin1'] ?? 'Kigali City',
                        'admin2' => $scenario['admin2'] ?? 'Nyarugenge',
                        'market' => $scenario['market'] ?? 'Kigali',
                        'latitude' => $scenario['latitude'] ?? -1.95,
                        'longitude' => $scenario['longitude'] ?? 30.06,
                        'category' => $scenario['category'] ?? 'cereals and tubers',
                        'commodity' => $scenario['commodity'] ?? 'Maize',
                        'currency' => 'RWF',
                        'pricetype' => 'Wholesale',
                        'recent_price' => $scenario['recent_price'] ?? 200.0,
                        'price_3_months_ago' => $scenario['recent_price'] ?? 200.0,
                        'price_avg_3_months' => $scenario['recent_price'] ?? 200.0,
                        'price_avg_6_months' => $scenario['recent_price'] ?? 200.0,
                    ];

                    // Create temp input file
                    $inputFile = storage_path('temp/batch_input_' . $index . '_' . uniqid() . '.json');
                    if (!file_exists(dirname($inputFile))) {
                        mkdir(dirname($inputFile), 0777, true);
                    }
                    file_put_contents($inputFile, json_encode($inputData));

                    // Run prediction
                    $process = new Process([
                        $this->pythonPath,
                        $this->scriptsPath . '/predict_crop_price.py',
                        '--model', $modelFile,
                        '--metadata', $metadataFile,
                        '--input', $inputFile
                    ]);

                    $process->setTimeout(60);
                    $process->run();

                    // Clean up
                    if (file_exists($inputFile)) {
                        unlink($inputFile);
                    }

                    if ($process->isSuccessful()) {
                        $output = $process->getOutput();
                        $result = $this->parsePredictionOutput($output);
                        $results[] = [
                            'scenario_index' => $index,
                            'success' => true,
                            'predicted_price' => $result['predicted_price'] ?? null,
                            'confidence_score' => $result['confidence_score'] ?? null,
                            'target_date' => $scenario['target_date'],
                            'market_id' => $scenario['market_id']
                        ];
                    } else {
                        $results[] = [
                            'scenario_index' => $index,
                            'success' => false,
                            'error' => $process->getErrorOutput(),
                            'target_date' => $scenario['target_date'],
                            'market_id' => $scenario['market_id']
                        ];
                    }

                } catch (\Exception $e) {
                    $results[] = [
                        'scenario_index' => $index,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'target_date' => $scenario['target_date'],
                        'market_id' => $scenario['market_id']
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'total_scenarios' => count($scenarios),
                'successful_predictions' => count(array_filter($results, function($r) { return $r['success']; }))
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrain model with new data
     */
    public function retrainModel(Request $request)
    {
        $request->validate([
            'commodity_id' => 'required|integer',
            'dataset_file' => 'required|string',
            'test_size' => 'numeric|min:0.1|max:0.5',
        ]);

        try {
            $commodityId = $request->commodity_id;
            $testSize = $request->test_size ?? 0.2;

            // Backup existing model if it exists
            $modelsDir = storage_path('app/models');
            $existingModel = glob($modelsDir . "/crop_{$commodityId}_*.pkl");
            
            if (!empty($existingModel)) {
                $backupDir = $modelsDir . '/backup_' . date('Y-m-d_H-i-s');
                if (!file_exists($backupDir)) {
                    mkdir($backupDir, 0777, true);
                }
                
                foreach (glob($modelsDir . "/crop_{$commodityId}_*") as $file) {
                    copy($file, $backupDir . '/' . basename($file));
                }
            }

            // Retrain model
            $process = new Process([
                $this->pythonPath,
                $this->scriptsPath . '/train_crop_model.py',
                '--dataset', $request->dataset_file,
                '--commodity-id', $commodityId,
                '--output', $modelsDir,
                '--test-size', $testSize
            ]);

            $process->setTimeout(1800);
            $process->run();

            if (!$process->isSuccessful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Retraining failed: ' . $process->getErrorOutput()
                ], 500);
            }

            $output = $process->getOutput();
            $result = $this->parseTrainingOutput($output);

            return response()->json([
                'success' => true,
                'message' => 'Model retrained successfully',
                'model_name' => $result['model_name'] ?? 'unknown',
                'accuracy' => $result['accuracy'] ?? 0,
                'backup_created' => !empty($existingModel)
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Parse training output from Python script
     */
    private function parseTrainingOutput($output)
    {
        // Look for JSON output in the training result
        $lines = explode("\n", $output);
        $jsonFound = false;
        $jsonString = '';

        foreach ($lines as $line) {
            if (strpos($line, 'TRAINING RESULT (JSON):') !== false) {
                $jsonFound = true;
                continue;
            }
            
            if ($jsonFound) {
                if (strpos($line, '=') === 0) {
                    break; // End of JSON section
                }
                $jsonString .= $line . "\n";
            }
        }

        if ($jsonString) {
            try {
                return json_decode(trim($jsonString), true);
            } catch (\Exception $e) {
                Log::error('Failed to parse training JSON: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Parse prediction output from Python script
     */
    private function parsePredictionOutput($output)
    {
        // Look for JSON output in the prediction result
        $lines = explode("\n", $output);
        $jsonFound = false;
        $jsonString = '';

        foreach ($lines as $line) {
            if (strpos($line, 'PREDICTION RESULT (JSON):') !== false) {
                $jsonFound = true;
                continue;
            }
            
            if ($jsonFound) {
                if (strpos($line, '=') === 0) {
                    break; // End of JSON section
                }
                $jsonString .= $line . "\n";
            }
        }

        if ($jsonString) {
            try {
                return json_decode(trim($jsonString), true);
            } catch (\Exception $e) {
                Log::error('Failed to parse prediction JSON: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Get model performance comparison
     */
    public function compareModels()
    {
        $modelsDir = storage_path('app/models');
        $summaryFiles = glob($modelsDir . '/crop_*_training_summary.csv');
        $comparisons = [];

        foreach ($summaryFiles as $file) {
            $filename = basename($file);
            if (preg_match('/crop_(\d+|all)_training_summary\.csv/', $filename, $matches)) {
                $commodityId = $matches[1];
                
                if (($handle = fopen($file, "r")) !== FALSE) {
                    $header = fgetcsv($handle);
                    $models = [];
                    
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        $models[] = array_combine($header, $data);
                    }
                    fclose($handle);
                    
                    $comparisons[$commodityId] = $models;
                }
            }
        }

        return response()->json(['comparisons' => $comparisons]);
    }

    /**
     * Export predictions to CSV
     */
    public function exportPredictions(Request $request)
    {
        $request->validate([
            'commodity_id' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
        ]);

        $query = Price_prediction::query();

        if ($request->commodity_id) {
            $query->where('crop_id', $request->commodity_id);
        }

        if ($request->date_from) {
            $query->where('prediction_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('prediction_date', '<=', $request->date_to);
        }

        $predictions = $query->with(['crop', 'region'])->get();

        $csvData = [];
        $csvData[] = [
            'Prediction ID', 'Commodity', 'Region', 'Predicted Price', 
            'Confidence Score', 'Model Used', 'Prediction Date', 
            'Target Date', 'Accuracy'
        ];

        foreach ($predictions as $prediction) {
            $csvData[] = [
                $prediction->id,
                $prediction->crop->name ?? 'Unknown',
                $prediction->region->name ?? 'Unknown',
                $prediction->predicted_price,
                $prediction->confidence_score,
                $prediction->model_used,
                $prediction->prediction_date,
                $prediction->target_date,
                $prediction->accuracy
            ];
        }

        $filename = 'crop_predictions_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/exports/' . $filename);

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0777, true);
        }

        $file = fopen($filepath, 'w');
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        return response()->download($filepath, $filename)->deleteFileAfterSend();
    }
}