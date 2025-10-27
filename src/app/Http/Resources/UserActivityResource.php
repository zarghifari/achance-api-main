<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserActivityResource extends JsonResource
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
            'user_id' => $this->user_id,
            'activity_type' => $this->activity_type,
            'activity_id' => $this->activity_id,
            'last_seen_url' => $this->last_seen_url,
            'last_seen_at' => $this->last_seen_at,
            'action' => $this->action,
            'progress_percentage' => $this->progress_percentage,
            'duration_seconds' => $this->duration_seconds,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'device_type' => $this->device_type,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
