<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizBulkImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->resource['success'] ?? true,
            'message' => $this->resource['message'] ?? 'Quiz imported successfully',
            'quiz_id' => $this->resource['quiz_id'] ?? null,
            'total_questions_imported' => $this->resource['total_questions_imported'] ?? 0,
            'total_answers_imported' => $this->resource['total_answers_imported'] ?? 0,
            'import_summary' => [
                'quiz_title' => $this->resource['quiz_title'] ?? null,
                'quiz_slug' => $this->resource['quiz_slug'] ?? null,
                'questions_count' => $this->resource['questions_count'] ?? 0,
                'question_types' => $this->resource['question_types'] ?? [],
                'imported_at' => $this->resource['imported_at'] ?? now()->toISOString(),
            ],
            'errors' => $this->resource['errors'] ?? [],
            'warnings' => $this->resource['warnings'] ?? [],
        ];
    }
}
