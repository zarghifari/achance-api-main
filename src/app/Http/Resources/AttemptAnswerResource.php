<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttemptAnswerResource extends JsonResource
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
            'attempt_id' => $this->attempt_id,
            'selected_answer_id' => $this->selected_answer_id,
            'quiz_question_id' => $this->quiz_question_id,
            'answer_text' => $this->answer_text,
            'is_correct' => $this->is_correct,
        ];
    }
}
