<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionsResource extends JsonResource
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
            'business_id' => $this->business_id,
            'promotion_title' => $this->promotion_title,
            'promotion_description' => $this->promotion_description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'promotion_type' => $this->promotion_type,
            'promotion_status' => $this->promotion_status,
            'discount_promotion' => $this->discount_promotion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}