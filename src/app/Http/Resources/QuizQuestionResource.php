<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'question_number' => $this->question_number,
            'question_text' => $this->question_text,
            'question_img' => $this->question_img,
            'question_type' => $this->question_type,
            'attachment' => $this->attachment,
            'quiz_answers' => QuizAnswerResource::collection($this->quizAnswers),
        ];
    }
}
