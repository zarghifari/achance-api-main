<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\QuizBulkImportService;
use Illuminate\Http\UploadedFile;

// Test CSV import
$filePath = '/tmp/template.csv';

// Create the service
$service = new QuizBulkImportService();

// Test CSV import
echo "Testing CSV import...\n";
$result = $service->importFromCsv($filePath, false);

echo "Result:\n";
echo json_encode($result, JSON_PRETTY_PRINT);
echo "\n";
