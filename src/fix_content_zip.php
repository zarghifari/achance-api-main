<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Content;

$content = Content::first();

if ($content) {
    $zipPath = 'uploads/contents/archives/import test_Pengantar Komputer Grafis_695a307fc6cb3.zip';
    $fullPath = storage_path('app/public/' . $zipPath);
    
    if (file_exists($fullPath)) {
        $content->zip_file_path = $zipPath;
        $content->zip_file_size = filesize($fullPath);
        $content->save();
        
        echo "Content updated successfully!" . PHP_EOL;
        echo "Content ID: " . $content->id . PHP_EOL;
        echo "ZIP Path: " . $content->zip_file_path . PHP_EOL;
        echo "ZIP Size: " . number_format($content->zip_file_size) . " bytes" . PHP_EOL;
        echo "ZIP Size: " . round($content->zip_file_size/1024/1024, 2) . " MB" . PHP_EOL;
    } else {
        echo "ERROR: ZIP file not found at: $fullPath" . PHP_EOL;
    }
} else {
    echo "ERROR: No content found in database" . PHP_EOL;
}
