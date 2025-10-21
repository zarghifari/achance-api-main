<?php

namespace App\Http\Resources;
use App\Http\Resources\EpubResource;

use App\Models\Epub;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonDetailResource extends JsonResource
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
            'module_id' => (int) $this->module_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'cover_image' => $this->cover_image,
            'video_url' => $this->video_url,
            'attachment' => $this->attachment,
            'position' => (int) $this->position,
            'description' => $this->description,
            'epub' => new EpubResource($this->epub), // Assuming a lesson has one epub
        ];
    }
}
