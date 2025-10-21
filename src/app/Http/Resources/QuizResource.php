<?php

namespace App\Http\Resources;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'summary' => $this->summary,
            'content' => $this->content,
            'published_at' => $this->published_at,
            'start_at' => $this->start_at,
            'ends_at' => $this->ends_at,
            'total_questions' => QuizQuestion::where('quiz_id', $this->id)->count(),
            'duration' => '30 minutes',
        ];
    }
}
