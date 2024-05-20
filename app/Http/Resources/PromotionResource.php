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
            'id' => $this->id,
            'promotion_uuid' => $this->promotion_uuid,
            'promotion_title' => $this->promotion_title,
            'promotion_description' => $this->promotion_description,
            'promotion_start_date' => $this->promotion_start_date,
            'promotion_end_date' => $this->promotion_end_date,
            'promotion_type' => $this->promotion_type,
            'promotion_status' => $this->promotion_status,
            'business_id' => $this->business_id,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,
            //'promotions_images' => new PromotionImageResource($this->promotionImages), // Aquí anidamos el recurso Branch
            'business' => new BusinessResource($this->business), // Aquí anidamos el recurso Business
        ];
    }
}
