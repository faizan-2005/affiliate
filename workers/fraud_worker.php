#!/usr/bin/env php
<?php

// fraud_worker.php - Background worker for fraud detection
// Run with: php workers/fraud_worker.php

require_once __DIR__ . '/../public/index.php';

use App\Core\Queue;

$queue = Queue::getInstance();

echo "Starting fraud detection worker...\n";

while (true) {
    $job = $queue->pop();

    if (!$job) {
        sleep(1);
        continue;
    }

    try {
        $jobClass = "App\\Jobs\\{$job['job']}";

        if (!class_exists($jobClass)) {
            throw new Exception("Job class not found: {$jobClass}");
        }

        $handler = new $jobClass($job['data']);
        $handler->handle();

        echo "[" . date('Y-m-d H:i:s') . "] ✓ {$job['job']} completed\n";

    } catch (\Exception $e) {
        log_error("Worker error: " . $e->getMessage());
        echo "[" . date('Y-m-d H:i:s') . "] ✗ {$job['job']} failed: " . $e->getMessage() . "\n";
    }
}
