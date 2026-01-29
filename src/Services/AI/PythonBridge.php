<?php

namespace App\Services\AI;

/**
 * Python Bridge (Phase 6)
 * 
 * Executes Python scripts for heavy ML computations (Prophet/LSTM)
 */
class PythonBridge
{
    private $pythonPath;
    private $scriptPath;

    public function __construct()
    {
        // Path to python executable (should be configured in .env)
        $this->pythonPath = getenv('PYTHON_PATH') ?: 'python3';
        $this->scriptPath = dirname(dirname(dirname(__DIR__))) . '/scripts/ai';
    }

    /**
     * Run Prophet Forecasting for a specific drug
     */
    public function runProphetForecast($drugId, array $history)
    {
        // 1. Prepare data (save to temporary CSV)
        $tmpFile = sys_get_temp_dir() . "/drug_$drugId" . "_history.json";
        file_put_contents($tmpFile, json_encode($history));

        // 2. Execute Python script
        $cmd = escapeshellcmd("{$this->pythonPath} {$this->scriptPath}/forecast_prophet.py $tmpFile");
        $output = shell_exec($cmd);

        // 3. Clean up
        unlink($tmpFile);

        return json_decode($output, true);
    }
}
