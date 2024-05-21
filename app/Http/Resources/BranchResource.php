<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    
    public function toArray(Request $request): array
    {
        $cover_images = $this->coverImages()->exists() ? $this->coverImages->map(function ($image) {
        return [
            'id' => $image->id,
            'branch_image_uuid' => $image->branch_image_uuid,
            'branch_image_path' => asset($image->branch_image_path),
            'branch_id' => $image->branch_id,
            'created_at' => $image->created_at->toDateTimeString(),  
            'updated_at' => $image->updated_at->toDateTimeString(),
        ];
    })->toArray() : [];


        return [
            'id' => $this->id,
            'branch_uuid' => $this->branch_uuid,
            'branch_logo' => asset($this->branch_logo),
            'branch_name' => $this->branch_name,
            'branch_email' => $this->branch_email,
            'branch_phone' => $this->branch_phone,
            'branch_address' => $this->branch_address,
            'branch_zipcode' => $this->branch_zipcode,
            'branch_city' => $this->branch_city,
            'branch_country' => $this->branch_country,
            'branch_website' => $this->branch_website,
            'branch_latitude' => (double) $this->branch_latitude,
            'branch_longitude' => (double) $this->branch_longitude,
            'business_id' => $this->business->id,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,

            'branch_cover_images' => $cover_images,
        ];
    }
}
