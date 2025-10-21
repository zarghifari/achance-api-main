<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuizDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'type' => $this->type,
            'published_at' => $this->published_at,
            'start_at' => $this->start_at,
            'ends_at' => $this->ends_at,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'quiz_questions' => QuizQuestionResource::collection($this->quizQuestions),
        ];
    }
}