<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuizBulkImportRequest;
use App\Http\Resources\QuizBulkImportResource;
use App\Services\QuizBulkImportService;
use App\Jobs\ProcessBulkImportJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;

class QuizBulkImportController extends Controller
{
    protected QuizBulkImportService $importService;

    public function __construct(QuizBulkImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Import quiz from uploaded file (JSON or CSV) - Async processing
     */
    public function import(QuizBulkImportRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $format = $request->input('format');
            $overwrite = $request->boolean('overwrite', false);
            $async = $request->boolean('async', true); // Default to async
            $userId = auth()->id() ?? 1; // Default user for testing

            // Store uploaded file temporarily
            $tempPath = $file->store('temp_imports');
            $fullPath = Storage::path($tempPath);

            // For small files or when async is disabled, process immediately
            $fileSize = $file->getSize();
            $sizeThreshold = 5 * 1024 * 1024; // 5MB

            if (!$async || $fileSize < $sizeThreshold) {
                // Process synchronously for small files
                $result = match ($format) {
                    'json' => $this->importService->importFromJson($fullPath, $overwrite),
                    'csv' => $this->importService->importFromCsv($fullPath, $overwrite),
                    'zip' => $this->importService->importFromZip($fullPath, $overwrite),
                    default => [
                        'success' => false,
                        'message' => 'Unsupported format',
                        'errors' => ['Invalid format specified']
                    ]
                };

                // Clean up temporary file
                Storage::delete($tempPath);

                return new QuizBulkImportResource($result);
            }

            // Process asynchronously for large files
            $jobId = uniqid('import_');
            ProcessBulkImportJob::dispatch($userId, $fullPath, $format, $overwrite, $file->getClientOriginalName())
                ->onQueue('bulk-imports');

            // Store job info for status checking
            $cacheKey = "bulk_import_job:{$userId}:{$jobId}";
            cache()->put($cacheKey, [
                'status' => 'processing',
                'filename' => $file->getClientOriginalName(),
                'format' => $format,
                'started_at' => now(),
                'file_size' => $fileSize
            ], 3600);

            return response()->json([
                'success' => true,
                'message' => 'Import job queued successfully',
                'job_id' => $jobId,
                'status' => 'processing',
                'check_status_url' => route('quiz.bulk-import.status', ['jobId' => $jobId])
            ], 202); // HTTP 202 Accepted

        } catch (\Exception $e) {
            Log::error('Bulk import failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName() ?? 'unknown',
                'error' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Check import job status
     */
    public function getImportStatus(Request $request, string $jobId): JsonResponse
    {
        $userId = auth()->id() ?? 1;
        $cacheKey = "bulk_import_job:{$userId}:{$jobId}";
        $jobInfo = cache()->get($cacheKey);

        if (!$jobInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ], 404);
        }

        // Check for completed result
        $resultKey = "bulk_import_result:{$userId}:" . md5($jobInfo['filename']);
        $result = cache()->get($resultKey);

        if ($result) {
            // Job completed or failed
            cache()->forget($cacheKey); // Clean up job info
            
            return response()->json([
                'success' => true,
                'status' => $result['status'],
                'result' => $result['result'] ?? null,
                'error' => $result['error'] ?? null,
                'execution_time' => $result['execution_time'] ?? null,
                'completed_at' => $result['completed_at'] ?? $result['failed_at'] ?? null
            ]);
        }

        // Job still processing
        return response()->json([
            'success' => true,
            'status' => 'processing',
            'filename' => $jobInfo['filename'],
            'started_at' => $jobInfo['started_at'],
            'processing_time' => now()->diffInSeconds($jobInfo['started_at'])
        ]);
    }

    /**
     * Download sample JSON template
     */
    public function downloadJsonTemplate(): JsonResponse
    {
        $template = [
            'title' => 'Sample Quiz Title',
            'slug' => 'sample-quiz-slug',
            'summary' => 'Brief description of the quiz',
            'type' => 'assessment',
            'content' => 'Detailed quiz instructions and content',
            'start_at' => '2024-01-15T09:00:00Z',
            'ends_at' => '2024-01-30T23:59:59Z',
            'questions' => [
                [
                    'question_number' => 1,
                    'question_text' => 'What is the capital of France?',
                    'question_img' => null,
                    'question_type' => 'multiple_choice',
                    'attachment' => null,
                    'answers' => [
                        [
                            'answer' => 'Paris',
                            'is_correct' => true
                        ],
                        [
                            'answer' => 'London',
                            'is_correct' => false
                        ],
                        [
                            'answer' => 'Berlin',
                            'is_correct' => false
                        ],
                        [
                            'answer' => 'Madrid',
                            'is_correct' => false
                        ]
                    ]
                ],
                [
                    'question_number' => 2,
                    'question_text' => 'The Earth is flat.',
                    'question_img' => null,
                    'question_type' => 'true_false',
                    'attachment' => null,
                    'answers' => [
                        [
                            'answer' => 'True',
                            'is_correct' => false
                        ],
                        [
                            'answer' => 'False',
                            'is_correct' => true
                        ]
                    ]
                ],
                [
                    'question_number' => 3,
                    'question_text' => 'Which of the following are programming languages? (Select all that apply)',
                    'question_img' => null,
                    'question_type' => 'multiple_correct_choice',
                    'attachment' => null,
                    'answers' => [
                        [
                            'answer' => 'Python',
                            'is_correct' => true
                        ],
                        [
                            'answer' => 'Java',
                            'is_correct' => true
                        ],
                        [
                            'answer' => 'HTML',
                            'is_correct' => false
                        ],
                        [
                            'answer' => 'JavaScript',
                            'is_correct' => true
                        ]
                    ]
                ],
                [
                    'question_number' => 4,
                    'question_text' => 'Explain the concept of object-oriented programming.',
                    'question_img' => null,
                    'question_type' => 'short_answer',
                    'attachment' => null,
                    'answers' => [
                        [
                            'answer' => 'Object-oriented programming is a programming paradigm based on the concept of objects, which contain data and code.',
                            'is_correct' => true
                        ]
                    ]
                ]
            ]
        ];

        return response()->json($template, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="quiz-template.json"'
        ]);
    }

    /**
     * Download sample CSV template
     */
    public function downloadCsvTemplate()
    {
        $csvContent = "title;slug;summary;type;content;start_at;ends_at;question_number;question_text;question_img;question_type;attachment;answer_1;is_correct_1;answer_2;is_correct_2;answer_3;is_correct_3;answer_4;is_correct_4;answer_5;is_correct_5\n";
        $csvContent .= "Sample Quiz Title;sample-quiz-slug;Brief description of the quiz;assessment;Detailed quiz instructions and content;2024-01-15T09:00:00Z;2024-01-30T23:59:59Z;1;What is the capital of France?;;multiple_choice;;Paris;true;London;false;Berlin;false;Madrid;false;\n";
        $csvContent .= ";;;;;;;;2;The Earth is flat.;;true_false;;True;false;False;true;;;;;;;;\n";
        $csvContent .= ";;;;;;;;3;Which of the following are programming languages? (Select all that apply);;multiple_correct_choice;;Python;true;Java;true;HTML;false;JavaScript;true;\n";
        $csvContent .= ";;;;;;;;4;Explain the concept of object-oriented programming.;;short_answer;;Object-oriented programming is a programming paradigm based on the concept of objects, which contain data and code.;true;;;;;;;;\n";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="quiz-template.csv"'
        ]);
    }

    /**
     * Get import format information and validation rules
     */
    public function getImportInfo(): JsonResponse
    {
        return response()->json([
            'supported_formats' => ['json', 'csv'],
            'max_file_size' => '10MB',
            'question_types' => [
                'multiple_choice' => [
                    'description' => 'Single correct answer from multiple options (2-5 answers)',
                    'correct_answers' => 'exactly 1'
                ],
                'true_false' => [
                    'description' => 'Boolean question with True/False answers',
                    'correct_answers' => 'exactly 1'
                ],
                'multiple_correct_choice' => [
                    'description' => 'Multiple correct answers from multiple options (2-5 answers)',
                    'correct_answers' => 'at least 1'
                ],
                'short_answer' => [
                    'description' => 'Open-ended text response',
                    'correct_answers' => 'exactly 1 (sample answer)'
                ]
            ],
            'csv_format' => [
                'delimiter' => 'semicolon (;)',
                'encoding' => 'UTF-8',
                'note' => 'For questions with fewer than 5 answers, leave extra answer columns empty'
            ],
            'required_fields' => [
                'quiz' => ['title', 'slug', 'type'],
                'questions' => ['question_number', 'question_text', 'question_type'],
                'answers' => ['answer', 'is_correct']
            ],
            'quiz_types' => ['assessment', 'practice', 'survey'],
            'validation_rules' => [
                'Quiz slug must be unique',
                'Question numbers should be sequential starting from 1',
                'Each question must have appropriate number of answers based on type',
                'At least one correct answer is required per question (except multiple_correct_choice which allows multiple)',
                'Date formats should be ISO 8601 (YYYY-MM-DDTHH:MM:SSZ)'
            ]
        ]);
    }
}
