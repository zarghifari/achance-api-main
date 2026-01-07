<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Content;

$content = Content::first();

if ($content) {
    echo "Content ID: {$content->id}\n";
    echo "Title: {$content->title}\n";
    echo "ZIP Path: {$content->zip_file_path}\n";
    echo "ZIP Size: " . number_format($content->zip_file_size) . " bytes (" . round($content->zip_file_size/1024/1024, 2) . " MB)\n";
    echo "Images Count: " . count($content->images) . "\n\n";
    
    if (!empty($content->images)) {
        echo "Image Details:\n";
        foreach ($content->images as $img) {
            echo "  - {$img['filename']} (" . round($img['size']/1024, 2) . " KB)\n";
        }
    }
} else {
    echo "No content found\n";
}
