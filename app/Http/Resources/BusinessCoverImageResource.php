<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessCoverImageResource extends JsonResource
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
            'business_image_uuid' => $this->business_image_uuid,
            'business_image_path' => asset($this->business_image_path),
            'business_id' => $this->business->business_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'business' => $this->business,
        ];
    }
}
