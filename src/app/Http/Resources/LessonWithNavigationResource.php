<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonWithNavigationResource extends JsonResource
{
    protected $nextLesson;
    protected $prevLesson;
    
    public function __construct($resource, $nextLesson = null, $prevLesson = null)
    {
        parent::__construct($resource);
        $this->nextLesson = $nextLesson;
        $this->prevLesson = $prevLesson;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'module_id' => (int) $this->module_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'cover_image' => $this->cover_image,
            'video_url' => $this->video_url,
            'attachment' => $this->attachment,
            'position' => (int) $this->position,
            'description' => $this->description,
            'navigation' => [
                'next_lesson' => $this->nextLesson ? [
                    'id' => $this->nextLesson->id,
                    'title' => $this->nextLesson->title,
                    'slug' => $this->nextLesson->slug,
                    'module_id' => $this->nextLesson->module_id
                ] : null,
                'prev_lesson' => $this->prevLesson ? [
                    'id' => $this->prevLesson->id,
                    'title' => $this->prevLesson->title,
                    'slug' => $this->prevLesson->slug,
                    'module_id' => $this->prevLesson->module_id
                ] : null
            ]
        ];
    }
}