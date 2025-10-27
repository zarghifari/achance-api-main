<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class EpubResource extends JsonResource
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
            'lesson_id' => $this->lesson_id,
            'title' => $this->title,
            'file_path' => $this->file_path,
            'original_filename' => $this->original_filename ?? null,
            'file_size' => $this->file_size ?? null,
            'mime_type' => $this->mime_type ?? 'application/epub+zip',
            'position' => $this->position ?? 0,
            'is_active' => $this->is_active ?? true,
            'file_info' => $this->getFileInfo(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get file information with error handling
     */
    private function getFileInfo(): array
    {
        if (!$this->file_path) {
            return [
                'download_url' => null,
                'file_hash' => null,
                'last_modified' => null,
            ];
        }

        $downloadUrl = url('storage/' . $this->file_path);
        $fullPath = public_path('storage/' . $this->file_path);

        // Check if file exists
        if (!file_exists($fullPath)) {
            Log::warning('EPUB file not found for resource', [
                'epub_id' => $this->id,
                'file_path' => $this->file_path,
                'full_path' => $fullPath
            ]);

            return [
                'download_url' => $downloadUrl,
                'file_hash' => null,
                'last_modified' => $this->updated_at?->timestamp,
                'file_exists' => false,
            ];
        }

        try {
            return [
                'download_url' => $downloadUrl,
                'file_hash' => md5_file($fullPath),
                'last_modified' => filemtime($fullPath),
                'file_exists' => true,
            ];
        } catch (\Exception $e) {
            Log::error('Error generating file info for EPUB', [
                'epub_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage()
            ]);

            return [
                'download_url' => $downloadUrl,
                'file_hash' => null,
                'last_modified' => $this->updated_at?->timestamp,
                'file_exists' => false,
                'error' => 'Could not generate file info'
            ];
        }
    }
}
