<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QuizBulkImportService;

class TestZipImport extends Command
{
    protected $signature = 'test:zip-import {file}';
    protected $description = 'Test ZIP import functionality';

    public function handle()
    {
        $file = $this->argument('file');
        
        $this->info("Testing ZIP import for file: $file");
        
        $service = new QuizBulkImportService();
        $result = $service->importFromZip($file, true);
        
        $this->info("Import Result:");
        $this->line(json_encode($result, JSON_PRETTY_PRINT));
        
        // Check upload directory
        $uploadDir = public_path('uploads/images');
        if (is_dir($uploadDir)) {
            $files = array_diff(scandir($uploadDir), ['.', '..']);
            $this->info("Files in upload directory:");
            foreach ($files as $file) {
                $this->line("- $file");
            }
        } else {
            $this->error("Upload directory does not exist");
        }
        
        return 0;
    }
}
