<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use App\Services\PerformanceOptimizationService;
use Exception;

class QuizBulkImportService
{
    protected array $errors = [];
    protected array $warnings = [];
    protected array $questionTypes = [];
    protected PerformanceOptimizationService $performanceService;

    public function __construct(PerformanceOptimizationService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Import quiz from JSON file
     */
    public function importFromJson(string $filePath, bool $overwrite = false): array
    {
        try {
            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON format: ' . json_last_error_msg());
            }

            return $this->processImportData($data, $overwrite);
        } catch (Exception $e) {
            Log::error('JSON import failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Import quiz from CSV file
     */
    public function importFromCsv(string $filePath, bool $overwrite = false): array
    {
        try {
            $csvData = $this->parseCsvFile($filePath);
            $quizData = $this->convertCsvToQuizFormat($csvData);
            
            return $this->processImportData($quizData, $overwrite);
        } catch (Exception $e) {
            Log::error('CSV import failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Import quiz from ZIP file containing JSON/CSV and images
     */
    public function importFromZip(string $filePath, bool $overwrite = false): array
    {
        try {
            $zip = new \ZipArchive();
            $result = $zip->open($filePath);
            
            if ($result !== TRUE) {
                throw new Exception('Cannot open ZIP file: ' . $result);
            }

            $tempDir = storage_path('app/temp_extract_' . uniqid());
            mkdir($tempDir, 0755, true);

            // Extract ZIP contents
            $zip->extractTo($tempDir);
            $zip->close();

            // Find JSON or CSV file
            $quizFile = null;
            $files = scandir($tempDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $quizFile = $tempDir . '/' . $file;
                    $format = 'json';
                    break;
                } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                    $quizFile = $tempDir . '/' . $file;
                    $format = 'csv';
                    break;
                }
            }

            if (!$quizFile) {
                throw new Exception('No JSON or CSV file found in ZIP');
            }

            // Process quiz data
            if ($format === 'json') {
                $quizData = $this->parseJsonFile($quizFile);
            } else {
                $csvData = $this->parseCsvFile($quizFile);
                $quizData = $this->convertCsvToQuizFormat($csvData);
            }

            // Process images and update quiz data
            $quizData = $this->processImagesFromZip($tempDir, $quizData);

            // Import the quiz
            $result = $this->processImportData($quizData, $overwrite);

            // Clean up temporary directory
            $this->deleteDirectory($tempDir);

            return $result;

        } catch (Exception $e) {
            Log::error('ZIP import failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Process images from extracted ZIP and update quiz data
     */
    protected function processImagesFromZip(string $tempDir, array $quizData): array
    {
        $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        $uploadPath = public_path('uploads/images');
        
        // Ensure upload directory exists
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Process each question for images
        foreach ($quizData['questions'] as &$question) {
            // Process question image
            if (!empty($question['question_img'])) {
                $imagePath = $tempDir . '/' . $question['question_img'];
                if (file_exists($imagePath)) {
                    $newFilename = $this->saveImageFile($imagePath, $uploadPath);
                    $question['question_img'] = $newFilename;
                }
            }

            // Process attachment (if it's an image)
            if (!empty($question['attachment'])) {
                $attachmentPath = $tempDir . '/' . $question['attachment'];
                if (file_exists($attachmentPath)) {
                    $ext = strtolower(pathinfo($question['attachment'], PATHINFO_EXTENSION));
                    if (in_array($ext, $imageExtensions)) {
                        $newFilename = $this->saveImageFile($attachmentPath, $uploadPath);
                        $question['attachment'] = $newFilename;
                    }
                }
            }
        }

        return $quizData;
    }

    /**
     * Save image file and return new filename
     */
    protected function saveImageFile(string $sourcePath, string $uploadPath): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destinationPath = $uploadPath . '/' . $filename;
        
        copy($sourcePath, $destinationPath);
        
        return $filename;
    }

    /**
     * Recursively delete directory
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Parse CSV file with semicolon delimiter
     */
    protected function parseCsvFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new Exception('Cannot open CSV file');
        }

        $headers = fgetcsv($handle, 0, ';');
        if ($headers === false) {
            throw new Exception('Cannot read CSV headers');
        }

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Convert CSV data to quiz format
     */
    protected function convertCsvToQuizFormat(array $csvData): array
    {
        if (empty($csvData)) {
            throw new Exception('CSV file is empty');
        }

        $firstRow = $csvData[0];
        $quizData = [
            'title' => $firstRow['title'] ?? '',
            'slug' => $firstRow['slug'] ?? '',
            'summary' => $firstRow['summary'] ?? null,
            'type' => $firstRow['type'] ?? 'assessment',
            'content' => $firstRow['content'] ?? null,
            'start_at' => $firstRow['start_at'] ?? null,
            'ends_at' => $firstRow['ends_at'] ?? null,
            'questions' => []
        ];

        $questionsData = [];
        
        foreach ($csvData as $row) {
            if (!empty($row['question_number']) && !empty($row['question_text'])) {
                $questionNumber = (int) $row['question_number'];
                
                if (!isset($questionsData[$questionNumber])) {
                    $questionsData[$questionNumber] = [
                        'question_number' => $questionNumber,
                        'question_text' => $row['question_text'],
                        'question_img' => $row['question_img'] ?: null,
                        'question_type' => $row['question_type'],
                        'attachment' => $row['attachment'] ?: null,
                        'answers' => []
                    ];
                }

                // Process answers (up to 5 answers)
                for ($i = 1; $i <= 5; $i++) {
                    $answerKey = "answer_$i";
                    $isCorrectKey = "is_correct_$i";
                    
                    if (!empty($row[$answerKey])) {
                        $questionsData[$questionNumber]['answers'][] = [
                            'answer' => $row[$answerKey],
                            'is_correct' => $this->parseBooleanValue($row[$isCorrectKey] ?? 'false')
                        ];
                    }
                }
            }
        }

        // Sort questions by question number and reindex
        ksort($questionsData);
        $quizData['questions'] = array_values($questionsData);

        return $quizData;
    }

    /**
     * Parse boolean values from CSV
     */
    protected function parseBooleanValue(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['true', '1', 'yes', 'y']);
    }

    /**
     * Process and validate import data
     */
    protected function processImportData(array $data, bool $overwrite = false): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->questionTypes = [];

        // Validate quiz data
        $validationResult = $this->validateQuizData($data);
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationResult['errors']
            ];
        }

        return DB::transaction(function () use ($data, $overwrite) {
            return $this->importQuizData($data, $overwrite);
        });
    }

    /**
     * Validate quiz data structure
     */
    protected function validateQuizData(array $data): array
    {
        $errors = [];

        // Validate required quiz fields
        $requiredFields = ['title', 'slug', 'type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Missing required field: $field";
            }
        }

        // Validate quiz type
        if (!empty($data['type']) && !in_array($data['type'], ['assessment', 'practice', 'survey'])) {
            $errors[] = "Invalid quiz type. Must be: assessment, practice, or survey";
        }

        // Validate questions
        if (empty($data['questions']) || !is_array($data['questions'])) {
            $errors[] = "Questions array is required and must not be empty";
        } else {
            foreach ($data['questions'] as $index => $question) {
                $questionErrors = $this->validateQuestionData($question, $index + 1);
                $errors = array_merge($errors, $questionErrors);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate individual question data
     */
    protected function validateQuestionData(array $question, int $questionIndex): array
    {
        $errors = [];
        $prefix = "Question $questionIndex: ";

        // Required fields
        if (empty($question['question_text'])) {
            $errors[] = $prefix . "question_text is required";
        }

        if (empty($question['question_type'])) {
            $errors[] = $prefix . "question_type is required";
        } elseif (!in_array($question['question_type'], [
            'multiple_choice', 'true_false', 'short_answer', 'multiple_correct_choice'
        ])) {
            $errors[] = $prefix . "Invalid question_type. Must be: multiple_choice, true_false, short_answer, or multiple_correct_choice";
        }

        // Track question types
        if (!empty($question['question_type'])) {
            $this->questionTypes[] = $question['question_type'];
        }

        // Validate answers
        if (empty($question['answers']) || !is_array($question['answers'])) {
            $errors[] = $prefix . "answers array is required";
        } else {
            $errors = array_merge($errors, $this->validateAnswers($question, $questionIndex));
        }

        return $errors;
    }

    /**
     * Validate question answers
     */
    protected function validateAnswers(array $question, int $questionIndex): array
    {
        $errors = [];
        $prefix = "Question $questionIndex: ";
        $answers = $question['answers'];
        $questionType = $question['question_type'] ?? '';

        // Check answer count based on question type
        switch ($questionType) {
            case 'true_false':
                if (count($answers) !== 2) {
                    $errors[] = $prefix . "true_false questions must have exactly 2 answers";
                }
                break;
            case 'multiple_choice':
                if (count($answers) < 2 || count($answers) > 5) {
                    $errors[] = $prefix . "multiple_choice questions must have 2-5 answers";
                }
                break;
            case 'multiple_correct_choice':
                if (count($answers) < 2 || count($answers) > 5) {
                    $errors[] = $prefix . "multiple_correct_choice questions must have 2-5 answers";
                }
                break;
            case 'short_answer':
                if (count($answers) !== 1) {
                    $errors[] = $prefix . "short_answer questions must have exactly 1 answer";
                }
                break;
        }

        // Validate correct answers
        $correctAnswersCount = 0;
        foreach ($answers as $answerIndex => $answer) {
            if (empty($answer['answer'])) {
                $errors[] = $prefix . "Answer " . ($answerIndex + 1) . " text cannot be empty";
            }

            if ($answer['is_correct'] ?? false) {
                $correctAnswersCount++;
            }
        }

        // Check correct answer requirements
        switch ($questionType) {
            case 'multiple_choice':
            case 'true_false':
                if ($correctAnswersCount !== 1) {
                    $errors[] = $prefix . "$questionType questions must have exactly 1 correct answer";
                }
                break;
            case 'multiple_correct_choice':
                if ($correctAnswersCount < 1) {
                    $errors[] = $prefix . "multiple_correct_choice questions must have at least 1 correct answer";
                }
                break;
            case 'short_answer':
                if ($correctAnswersCount !== 1) {
                    $errors[] = $prefix . "short_answer questions must have exactly 1 correct answer";
                }
                break;
        }

        return $errors;
    }

    /**
     * Import quiz data into database
     */
    protected function importQuizData(array $data, bool $overwrite = false): array
    {
        try {
            // Check if quiz exists
            $existingQuiz = Quiz::where('slug', $data['slug'])->first();
            
            if ($existingQuiz && !$overwrite) {
                return [
                    'success' => false,
                    'message' => 'Quiz with this slug already exists. Use overwrite option to replace it.',
                    'errors' => ['Quiz slug already exists']
                ];
            }

            // Delete existing quiz if overwriting
            if ($existingQuiz && $overwrite) {
                $existingQuiz->delete();
                $this->warnings[] = 'Existing quiz was replaced';
            }

            // Create quiz
            $quizData = [
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'] ?? null,
                'type' => $data['type'],
                'content' => $data['content'] ?? null,
                'published_at' => now(),
                'start_at' => $data['start_at'] ? \Carbon\Carbon::parse($data['start_at']) : now(),
                'ends_at' => $data['ends_at'] ? \Carbon\Carbon::parse($data['ends_at']) : null,
            ];

            $quiz = Quiz::create($quizData);

            // Import questions and answers
            $totalQuestions = 0;
            $totalAnswers = 0;

            foreach ($data['questions'] as $questionData) {
                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_number' => $questionData['question_number'],
                    'question_text' => $questionData['question_text'],
                    'question_img' => $questionData['question_img'] ?? null,
                    'question_type' => $questionData['question_type'],
                    'attachment' => $questionData['attachment'] ?? null,
                ]);

                $totalQuestions++;

                // Import answers
                foreach ($questionData['answers'] as $answerData) {
                    QuizAnswer::create([
                        'quiz_question_id' => $question->id,
                        'answer' => $answerData['answer'],
                        'is_correct' => $answerData['is_correct'] ?? false,
                    ]);

                    $totalAnswers++;
                }
            }

            return [
                'success' => true,
                'message' => 'Quiz imported successfully',
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'quiz_slug' => $quiz->slug,
                'total_questions_imported' => $totalQuestions,
                'total_answers_imported' => $totalAnswers,
                'questions_count' => count($data['questions']),
                'question_types' => array_unique($this->questionTypes),
                'imported_at' => now()->toISOString(),
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];

        } catch (Exception $e) {
            Log::error('Quiz import failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
