<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class TrainCropModelCommand extends Command
{
    protected $signature = 'crop:train {--commodity-id=} {--dataset=} {--test-size=0.2}';
    protected $description = 'Train crop price prediction model using Python ML';

    public function handle()
    {
        $commodityId = $this->option('commodity-id');
        $datasetPath = $this->option('dataset');
        $testSize = $this->option('test-size');

        if (!$datasetPath) {
            $this->error('Dataset path is required. Use --dataset=/path/to/your/dataset.csv');
            return 1;
        }

        if (!file_exists($datasetPath)) {
            $this->error("Dataset file not found: {$datasetPath}");
            return 1;
        }

        $this->info('Starting model training...');
        $this->info("Dataset: {$datasetPath}");
        $this->info("Commodity ID: " . ($commodityId ?? 'all'));
        $this->info("Test size: {$testSize}");

        try {
            $pythonPath = config('app.python_path', 'python3');
            $scriptsPath = base_path('python'); // Make sure your train_crop_model.py is in /python
            $modelsDir = storage_path('app/models');

            if (!file_exists($modelsDir)) {
                mkdir($modelsDir, 0777, true);
            }

            $arguments = [
                $pythonPath,
                $scriptsPath . '/train_crop_model.py',
                '--dataset', $datasetPath,
                '--output', $modelsDir,
                '--test-size', $testSize
            ];

            if ($commodityId) {
                $arguments[] = '--commodity-id';
                $arguments[] = $commodityId;
            }

            $process = new Process($arguments);
            $process->setTimeout(1800);

            $this->info('Training in progress...');

            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }
            });

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $this->info('Model training completed successfully!');
            return 0;

        } catch (ProcessFailedException $e) {
            $this->error('Training process failed:');
            $this->error($e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error('Training failed:');
            $this->error($e->getMessage());
            return 1;
        }
    }
}
