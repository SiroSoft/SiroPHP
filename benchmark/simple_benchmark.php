<?php

/**
 * Simple PHP Benchmark Script for SiroPHP v0.7.10
 * 
 * This script sends concurrent HTTP requests and measures performance
 */

$baseUrl = 'http://localhost:8080';
$totalRequests = 5000; // Increased for better accuracy
$concurrency = 20; // Higher concurrency

echo "=== SiroPHP v0.7.10 Benchmark ===\n";
echo "URL: $baseUrl\n";
echo "Total Requests: $totalRequests\n";
echo "Concurrency: $concurrency\n";
echo str_repeat('=', 50) . "\n\n";

// Test endpoints
$endpoints = [
    '/' => 'Root endpoint (static JSON)',
];

foreach ($endpoints as $endpoint => $description) {
    echo "Testing: $description ($endpoint)\n";
    echo str_repeat('-', 50) . "\n";
    
    $url = $baseUrl . $endpoint;
    $results = [];
    $errors = 0;
    
    // Warm up
    for ($i = 0; $i < 10; $i++) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }
    
    // Benchmark
    $startTime = microtime(true);
    $successCount = 0;
    $latencies = [];
    
    for ($i = 0; $i < $totalRequests; $i += $concurrency) {
        $batchStart = microtime(true);
        $handles = [];
        $mh = curl_multi_init();
        
        // Create batch
        for ($j = 0; $j < min($concurrency, $totalRequests - $i); $j++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }
        
        // Execute batch
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);
        
        // Collect results
        foreach ($handles as $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000; // Convert to ms
            
            if ($httpCode === 200) {
                $successCount++;
                $latencies[] = $totalTime;
            } else {
                $errors++;
            }
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($mh);
    }
    
    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;
    
    // Calculate statistics
    sort($latencies);
    $count = count($latencies);
    
    if ($count > 0) {
        $min = round($latencies[0], 2);
        $max = round($latencies[$count - 1], 2);
        $avg = round(array_sum($latencies) / $count, 2);
        $p50 = round($latencies[intval($count * 0.5)], 2);
        $p95 = round($latencies[intval($count * 0.95)], 2);
        $p99 = round($latencies[intval($count * 0.99)], 2);
        $rps = round($successCount / $totalTime, 2);
        $errorRate = round(($errors / $totalRequests) * 100, 2);
        
        echo "  Total Time:      " . round($totalTime, 2) . "s\n";
        echo "  Requests/sec:    $rps\n";
        echo "  Success Rate:    " . round(($successCount / $totalRequests) * 100, 2) . "%\n";
        echo "  Error Rate:      {$errorRate}%\n";
        echo "  Latency (ms):\n";
        echo "    Min:           $min\n";
        echo "    Avg:           $avg\n";
        echo "    Max:           $max\n";
        echo "    p50:           $p50\n";
        echo "    p95:           $p95\n";
        echo "    p99:           $p99\n";
    } else {
        echo "  ERROR: No successful requests!\n";
    }
    
    echo "\n";
}

echo str_repeat('=', 50) . "\n";
echo "Benchmark completed!\n";
