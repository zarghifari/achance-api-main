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
            'user_id' => $this->user_id,
            'activity_type' => $this->activity_type,
            'activity_id' => $this->activity_id,
            'last_seen_url' => $this->last_seen_url,
            'last_seen_at' => $this->last_seen_at,
        ];
    }
}
