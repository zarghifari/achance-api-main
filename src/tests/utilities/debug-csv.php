<?php

$filePath = '/tmp/corrected-csv.csv';
$handle = fopen($filePath, 'r');

if ($handle === false) {
    echo "Cannot open CSV file\n";
    exit;
}

$headers = fgetcsv($handle, 0, ';');
echo "Headers count: " . count($headers) . "\n";
echo "Headers: " . json_encode($headers) . "\n\n";

$lineNumber = 1;
while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $lineNumber++;
    echo "Line $lineNumber count: " . count($row) . "\n";
    echo "Line $lineNumber data: " . json_encode($row) . "\n";
    
    if (count($row) === count($headers)) {
        echo "✓ Row matches header count\n";
    } else {
        echo "✗ Row count mismatch\n";
    }
    echo "\n";
}

fclose($handle);
