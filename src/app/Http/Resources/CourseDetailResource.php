<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDetailResource extends JsonResource
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
            'description' => $this->description,
            'course_img' => $this->course_img,
            'video_url' => $this->video_url,
            'isOpen' => $this->isOpen,
            'total_hours' => $this->total_hours,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'modules' => $this->whenLoaded('modules', function () {
                return ModuleResource::collection($this->modules);
            }),
        ];
    }
    
    /**
     * Customize the response to reduce overhead
     */
    public function withResponse($request, $response)
    {
        // Set cache headers for better performance
        $response->header('Cache-Control', 'public, max-age=300');
    }
}
