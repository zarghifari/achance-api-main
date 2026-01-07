<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Content;

echo "=== Content Image Verification ===\n\n";

$content = Content::latest()->first();

if (!$content) {
    echo "No content found in database.\n";
    exit;
}

echo "Content ID: {$content->id}\n";
echo "Title: {$content->title}\n";
echo "Lesson ID: {$content->lesson_id}\n\n";

echo "Images stored: " . count($content->images) . "\n";
if (!empty($content->images)) {
    echo "\nImage details:\n";
    foreach ($content->images as $index => $image) {
        $originalName = isset($image['original_filename']) ? $image['original_filename'] : $image['filename'];
        echo "  [{$index}] {$originalName}\n";
        echo "      Path: {$image['path']}\n";
        echo "      Storage: {$image['storage_path']}\n";
        $accessible = isset($image['accessible_via_html']) ? ($image['accessible_via_html'] ? 'Yes' : 'No') : 'N/A';
        echo "      Accessible: {$accessible}\n";
        echo "      Size: " . number_format($image['size']) . " bytes\n";
        
        // Check if file exists
        $fullPath = storage_path('app/public/' . $image['path']);
        $exists = file_exists($fullPath);
        echo "      File exists: " . ($exists ? 'Yes' : 'No') . "\n\n";
    }
}

// Check HTML content for image references
if ($content->html_content) {
    preg_match_all('/src=["\']([^"\']*image[^"\']*)["\']/', $content->html_content, $matches);
    
    if (!empty($matches[1])) {
        echo "\nImage references in HTML (" . count($matches[1]) . " found):\n";
        foreach (array_unique($matches[1]) as $imagePath) {
            echo "  - {$imagePath}\n";
        }
    } else {
        echo "\nNo image references found in HTML content.\n";
    }
}

echo "\n=== Verification Complete ===\n";
