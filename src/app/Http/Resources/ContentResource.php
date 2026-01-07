<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get course and module IDs
        $lesson = $this->lesson;
        $moduleId = $lesson->module_id ?? null;
        $courseId = $lesson->module->course_id ?? null;

        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'title' => $this->title,
            'type' => $this->type,
            'description' => $this->description,
            'original_filename' => $this->original_filename,
            'source_file_path' => $this->source_file_path ? url('storage/' . $this->source_file_path) : null,
            'source_file_size' => $this->source_file_size,
            'total_pages' => $this->total_pages,
            'words_per_page' => $this->words_per_page,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'is_processed' => $this->is_processed,
            'processed_at' => $this->processed_at,
            'metadata' => $this->metadata,
            'images' => $this->processImagesWithUrls($courseId, $moduleId),
            'assets' => $this->assets,
            'pagination_info' => [
                'total_pages' => $this->total_pages,
                'words_per_page' => $this->words_per_page,
                'page_endpoints' => $this->getPageEndpoints()
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Process images array to include full URLs
     */
    private function processImagesWithUrls($courseId, $moduleId): array
    {
        $images = $this->images ?? [];
        $processedImages = [];

        foreach ($images as $image) {
            $imageData = $image;
            
            // If it's an uploaded image, provide both storage URL and API URL
            if (isset($image['processed_path']) && isset($image['type']) && $image['type'] === 'uploaded') {
                $imageData['url'] = url($image['processed_path']);
                
                // Also provide API endpoint URL for serving the file
                if ($courseId && $moduleId) {
                    $filename = basename($image['processed_path']);
                    $imageData['api_url'] = url("api/courses/{$courseId}/modules/{$moduleId}/lessons/{$this->lesson_id}/content/files/images/{$filename}");
                }
            }
            
            $processedImages[] = $imageData;
        }

        return $processedImages;
    }

    /**
     * Generate page endpoints for easy access
     */
    private function getPageEndpoints(): array
    {
        $endpoints = [];
        for ($i = 1; $i <= $this->total_pages; $i++) {
            $endpoints[] = [
                'page' => $i,
                'url' => url("api/courses/{$this->lesson->module->course_id}/modules/{$this->lesson->module_id}/lessons/{$this->lesson_id}/content/page/{$i}")
            ];
        }
        return $endpoints;
    }
}
