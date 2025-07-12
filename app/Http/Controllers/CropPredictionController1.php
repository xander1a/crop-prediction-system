<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\PredictionResult;

class CropPredictionController1 extends Controller
{
    public function showForm()
    {
        return view('formfinal');
    }

    public function predict(Request $request)
    {
        $validated = $request->validate([
           
            'market_id' => 'required|numeric',
            'commodity_id' => 'required|numeric',
            'admin1' => 'required|string',
            'admin2' => 'required|string',
            'market' => 'required|string',
            'category' => 'required|string',
            'commodity' => 'required|string',
            'currency' => 'required|string',
            'pricetype' => 'required|string',
            'unit' => 'required|string',
            'target_date' => 'required|date'
        ]);

        // Add target_date (current date or future date)
        $validated['target_date'] = $validated['target_date'] ?? now()->toDateString();

        $modelPath = storage_path('app/models/crop_51_svr_linear.pkl');
        $metadataPath = storage_path('app/models/crop_51_metadata.pkl');

        // Check if model files exist
        if (!file_exists($modelPath) || !file_exists($metadataPath)) {
            return back()->with('error', 'Model files not found. Please train the model first.');
        }

        // Create input JSON with proper formatting
        $inputJson = json_encode($validated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        // Create temp directory if it doesn't exist
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $inputFile = $tempDir . '/prediction_input_' . time() . '.json';
        $outputFile = $tempDir . '/prediction_output_' . time() . '.json';
        
        // Write JSON to file with error checking
        if (file_put_contents($inputFile, $inputJson) === false) {
            return back()->with('error', 'Failed to create temporary input file.');
        }

        // Verify the file was written correctly
        $fileContent = file_get_contents($inputFile);
        if (empty($fileContent)) {
            unlink($inputFile);
            return back()->with('error', 'Input file is empty after writing.');
        }

        Log::info('Input JSON created', [
            'file' => $inputFile,
            'size' => filesize($inputFile),
            'content_preview' => substr($fileContent, 0, 200)
        ]);

        // Find Python executable
        $pythonPath = $this->findPythonExecutable();
        if (!$pythonPath) {
            unlink($inputFile);
            return back()->with('error', 'Python is not installed or not found in PATH. Please install Python and add it to your system PATH.');
        }

        Log::info("Using Python executable: " . $pythonPath);

        // Use absolute paths
        $scriptPath = base_path('python/predict_crop_price.py');
        
        // Verify script exists
        if (!file_exists($scriptPath)) {
            unlink($inputFile);
            return back()->with('error', 'Python prediction script not found at: ' . $scriptPath);
        }

        // Convert paths to use forward slashes (Windows Python handles this better)
        $modelPath = str_replace('\\', '/', $modelPath);
        $metadataPath = str_replace('\\', '/', $metadataPath);
        $inputFile = str_replace('\\', '/', $inputFile);
        $outputFile = str_replace('\\', '/', $outputFile);
        $scriptPath = str_replace('\\', '/', $scriptPath);

        // Build command as a string to avoid Windows process issues
        $command = sprintf(
            '"%s" "%s" --model "%s" --metadata "%s" --input "%s" --output "%s" --debug 2>&1',
            $pythonPath,
            $scriptPath,
            $modelPath,
            $metadataPath,
            $inputFile,
            $outputFile
        );

        Log::info('Executing command', ['command' => $command]);

        try {
            // Use exec() instead of Symfony Process to avoid Windows socket issues
            $output = [];
            $returnVar = 0;
            
            // Execute the command
            exec($command, $output, $returnVar);
            
            // Join output lines
            $outputString = implode("\n", $output);
            
            Log::info('Process completed', [
                'return_code' => $returnVar,
                'output_lines' => count($output),
                'output_preview' => substr($outputString, 0, 500)
            ]);

            // Clean up input file
            if (file_exists(str_replace('/', '\\', $inputFile))) {
                unlink(str_replace('/', '\\', $inputFile));
            }

            // Check if the process was successful
            if ($returnVar !== 0) {
                Log::error('Python process failed', [
                    'return_code' => $returnVar,
                    'output' => $outputString,
                    'command' => $command
                ]);
                
                return back()->with('error', 'Prediction failed: ' . $outputString);
            }

            // Try to read from output file first
            $result = null;
            $outputFilePath = str_replace('/', '\\', $outputFile);
            
            if (file_exists($outputFilePath)) {
                $fileContent = file_get_contents($outputFilePath);
                if ($fileContent) {
                    $result = json_decode($fileContent, true);
                    Log::info('Result from output file', ['result' => $result]);
                }
                unlink($outputFilePath);
            }

            // If no result from file, parse the output string
            if (!$result) {
                $result = $this->parseProcessOutput($outputString);
            }
            
            if (!$result) {
                Log::error('Failed to parse prediction result', [
                    'raw_output' => $outputString
                ]);
                return back()->with('error', 'Failed to parse prediction result. Check logs for details.');
            }

            if (!$result['success']) {
                return back()->with('error', 'Prediction failed: ' . ($result['error'] ?? 'Unknown error'));
            }

            




            // Store in DB
$prediction = new PredictionResult();
$prediction->predicted_price = $result['predicted_price'];
$prediction->confidence_score = $result['confidence_score'];
$prediction->model_used = $result['model_used'];
$prediction->prediction_date = $result['prediction_date'];
$prediction->target_date = $result['target_date'];
$prediction->commodity_id = $result['commodity_id'];
$prediction->admin1 = $result['admin1'] ?? $validated['admin1'];
$prediction->admin2 = $result['admin2'] ?? $validated['admin2'];
$prediction->market = $result['market'] ?? $validated['market'];
$prediction->crop_name = $result['crop_name'] ?? $validated['commodity'];
$prediction->save();

return view('result', compact('result'));

        } catch (\Exception $e) {
            // Clean up temp files on exception
            if (file_exists(str_replace('/', '\\', $inputFile))) {
                unlink(str_replace('/', '\\', $inputFile));
            }
            if (file_exists(str_replace('/', '\\', $outputFile))) {
                unlink(str_replace('/', '\\', $outputFile));
            }
            
            Log::error('Exception during prediction', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Prediction failed: ' . $e->getMessage());
        }
    }

    private function findPythonExecutable()
    {
        $pythonExecutables = [
            'python',
            'python3',
            'py',
            'C:/Python/python.exe',
            'C:/Python39/python.exe',
            'C:/Python310/python.exe',
            'C:/Python311/python.exe',
            'C:/Python312/python.exe',
            'C:/Python313/python.exe',
        ];

        // Add user-specific paths
        $username = get_current_user();
        if ($username) {
            $pythonExecutables[] = "C:/Users/{$username}/AppData/Local/Programs/Python/Python311/python.exe";
            $pythonExecutables[] = "C:/Users/{$username}/AppData/Local/Programs/Python/Python312/python.exe";
            $pythonExecutables[] = "C:/Users/{$username}/AppData/Local/Programs/Python/Python313/python.exe";
        }

        foreach ($pythonExecutables as $executable) {
            // Test if executable works
            $testCommand = sprintf('"%s" --version 2>&1', $executable);
            $output = [];
            $returnVar = 0;
            
            exec($testCommand, $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output)) {
                $version = implode(' ', $output);
                Log::info("Found Python: {$executable} - {$version}");
                return $executable;
            }
        }

        return null;
    }

    private function parseProcessOutput($output)
    {
        // Look for JSON in the output
        $patterns = [
            '/PREDICTION RESULT \(JSON\):\s*(\{.*\})/s',
            '/PREDICTION ERROR \(JSON\):\s*(\{.*\})/s',
            '/(\{.*"success".*\})/s'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $matches)) {
                $jsonString = trim($matches[1]);
                $result = json_decode($jsonString, true);
                
                if ($result !== null) {
                    return $result;
                }
            }
        }

        // If no JSON pattern found, try to decode the entire output
        $trimmedOutput = trim($output);
        if (!empty($trimmedOutput)) {
            $result = json_decode($trimmedOutput, true);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}