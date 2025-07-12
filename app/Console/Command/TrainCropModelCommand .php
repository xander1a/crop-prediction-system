<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[\Illuminate\Console\Attributes\AsCommand(
    name: 'crop:train',
    description: 'Train crop price prediction model using Python ML'
)]
class TrainCropModelCommand extends Command
{
    public function handle()
    {
        $commodityId = $this->ask('Enter commodity ID');
        $datasetPath = $this->ask('Enter dataset path (e.g., storage/app/datasets/maize.csv)');
        $testSize = $this->ask('Enter test size split (default 0.2)', 0.2);

        if (!file_exists($datasetPath)) {
            $this->error("Dataset file not found at: $datasetPath");
            return 1;
        }

        $pythonPath = config('app.python_path', 'python');
        $scriptsPath = base_path('python');
        $modelsDir = storage_path('app/models');

        if (!file_exists($modelsDir)) {
            mkdir($modelsDir, 0777, true);
        }

        $arguments = [
            $pythonPath,
            "$scriptsPath/train_crop_model.py",
            '--dataset', $datasetPath,
            '--output', $modelsDir,
            '--test-size', $testSize,
            '--commodity-id', $commodityId
        ];

        $process = new Process($arguments);
        $process->setTimeout(1800);

        $this->info("Training started...");

        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->info("Training complete.");
        return 0;
    }
}
