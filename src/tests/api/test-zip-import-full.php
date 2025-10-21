<?php

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesApplication;
use App\Services\QuizBulkImportService;

class ZipImportTest extends TestCase
{
    use CreatesApplication;

    public function testZipImport()
    {
        echo "Testing ZIP import functionality...\n";
        
        $service = new QuizBulkImportService();
        $result = $service->importFromZip('/tmp/quiz-with-images.zip', true);
        
        echo "Import Result:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
        // Check if images were copied
        $uploadDir = public_path('uploads/images');
        echo "\nChecking upload directory: $uploadDir\n";
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            echo "Files in upload directory:\n";
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    echo "- $file\n";
                }
            }
        } else {
            echo "Upload directory does not exist\n";
        }
    }
}

// Run the test
$test = new ZipImportTest();
$test->setUp();
$test->testZipImport();
