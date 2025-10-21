<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\QuizBulkImportService;
use App\Models\User;

class ProcessBulkImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $filePath;
    protected $format;
    protected $overwrite;
    protected $originalFilename;

    public $timeout = 3600; // 1 hour
    public $tries = 3;

    public function __construct(int $userId, string $filePath, string $format, bool $overwrite, string $originalFilename)
    {
        $this->userId = $userId;
        $this->filePath = $filePath;
        $this->format = $format;
        $this->overwrite = $overwrite;
        $this->originalFilename = $originalFilename;
    }

    public function handle(QuizBulkImportService $importService)
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Starting bulk import job', [
                'user_id' => $this->userId,
                'file' => $this->originalFilename,
                'format' => $this->format
            ]);

            // Process the import based on format
            $result = match($this->format) {
                'json' => $importService->importFromJson($this->filePath, $this->overwrite),
                'csv' => $importService->importFromCsv($this->filePath, $this->overwrite),
                'zip' => $importService->importFromZip($this->filePath, $this->overwrite),
                default => throw new \Exception('Unsupported format: ' . $this->format)
            };

            $executionTime = microtime(true) - $startTime;

            // Store result in cache for user to retrieve
            $cacheKey = "bulk_import_result:{$this->userId}:" . md5($this->originalFilename);
            cache()->put($cacheKey, [
                'status' => 'completed',
                'result' => $result,
                'execution_time' => round($executionTime, 2),
                'completed_at' => now()
            ], 3600); // Cache for 1 hour

            // Notify user (if notification system exists)
            $this->notifyUser($result, $executionTime);

            Log::info('Bulk import job completed successfully', [
                'user_id' => $this->userId,
                'file' => $this->originalFilename,
                'execution_time' => round($executionTime, 2),
                'quiz_id' => $result['quiz_id'] ?? null
            ]);

        } catch (\Exception $e) {
            $this->handleFailure($e);
            throw $e;
        } finally {
            // Cleanup temporary file
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Bulk import job failed', [
            'user_id' => $this->userId,
            'file' => $this->originalFilename,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Store failure result in cache
        $cacheKey = "bulk_import_result:{$this->userId}:" . md5($this->originalFilename);
        cache()->put($cacheKey, [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'failed_at' => now()
        ], 3600);

        // Cleanup temporary file
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }
    }

    protected function handleFailure(\Exception $e): void
    {
        $cacheKey = "bulk_import_result:{$this->userId}:" . md5($this->originalFilename);
        cache()->put($cacheKey, [
            'status' => 'failed',
            'error' => $e->getMessage(),
            'failed_at' => now()
        ], 3600);
    }

    protected function notifyUser(array $result, float $executionTime): void
    {
        // Implement user notification logic here
        // Could be email, websocket, database notification, etc.
        
        Log::info('User notification sent', [
            'user_id' => $this->userId,
            'success' => $result['success'] ?? false,
            'execution_time' => $executionTime
        ]);
    }
}
