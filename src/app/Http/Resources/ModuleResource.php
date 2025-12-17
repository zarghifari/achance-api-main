<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'cover_image' => $this->cover_image,
            'video_url' => $this->video_url,
            'position' => (int) $this->position,
            'description' => $this->description,
            'course_id' => (int) $this->course_id,
            'lessons' => $this->whenLoaded('lessons', function () {
                return LessonResource::collection($this->lessons);
            }),
            'tasks' => $this->whenLoaded('tasks', function () {
                return TaskResource::collection($this->tasks);
            }),
        ];
    }
}
