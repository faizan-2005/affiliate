#!/usr/bin/env php
<?php

// archive_worker.php - Background worker for archiving old clicks
// Run with: php workers/archive_worker.php

require_once __DIR__ . '/../public/index.php';

use App\Jobs\ArchiveClicksJob;

echo "Starting archive worker...\n";

while (true) {
    try {
        // Run archival once a day at midnight
        $now = new DateTime();
        $nextRun = new DateTime('tomorrow');
        $nextRun->setTime(0, 0, 0);
        
        $sleepSeconds = $nextRun->getTimestamp() - $now->getTimestamp();
        
        echo "[" . date('Y-m-d H:i:s') . "] Next run in {$sleepSeconds} seconds\n";
        
        sleep($sleepSeconds);
        
        $job = new ArchiveClicksJob();
        $job->handle();
        
        echo "[" . date('Y-m-d H:i:s') . "] ✓ Archive job completed\n";

    } catch (\Exception $e) {
        log_error("Archive worker error: " . $e->getMessage());
        echo "[" . date('Y-m-d H:i:s') . "] ✗ Error: " . $e->getMessage() . "\n";
        sleep(3600);
    }
}
