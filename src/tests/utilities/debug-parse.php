<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\QuizBulkImportService;

// Create the service and access the protected method using reflection
$service = new QuizBulkImportService();
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('parseCsvFile');
$method->setAccessible(true);

$filePath = '/tmp/template.csv';

try {
    $result = $method->invoke($service, $filePath);
    echo "CSV parsed successfully!\n";
    echo "Number of rows: " . count($result) . "\n";
    echo "First row data:\n";
    echo json_encode($result[0] ?? [], JSON_PRETTY_PRINT);
    echo "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
