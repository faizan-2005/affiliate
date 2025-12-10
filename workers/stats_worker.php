#!/usr/bin/env php
<?php

// stats_worker.php - Background worker for stats rollup
// Run with: php workers/stats_worker.php

require_once __DIR__ . '/../public/index.php';

use App\Jobs\StatsRollupJob;

echo "Starting stats rollup worker...\n";

while (true) {
    try {
        // Run stats rollup every hour
        $job = new StatsRollupJob();
        $job->handle();
        
        echo "[" . date('Y-m-d H:i:s') . "] ✓ Stats rollup completed\n";
        
        // Sleep for 1 hour
        sleep(3600);

    } catch (\Exception $e) {
        log_error("Stats worker error: " . $e->getMessage());
        echo "[" . date('Y-m-d H:i:s') . "] ✗ Error: " . $e->getMessage() . "\n";
        sleep(60);
    }
}
