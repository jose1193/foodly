<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'promotion_uuid' => $this->promotion_uuid,
            'promotion_title' => $this->promotion_title,
            'promotion_description' => $this->promotion_description,
            'promotion_start_date' => $this->promotion_start_date,
            'promotion_end_date' => $this->promotion_end_date,
            'promotion_type' => $this->promotion_type,
            'promotion_status' => $this->promotion_status,
            'business_id' => $this->business_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            //'business' => new BusinessResource($this->business), // Aquí anidamos el recurso Business
        ];
    }
}
