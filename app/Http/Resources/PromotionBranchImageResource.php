<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionBranchImageResource extends JsonResource
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
            'promotion_branch_image_uuid' => $this->promotion_branch_image_uuid,
            'promotion_branch_image_path' => asset($this->promotion_branch_image_path),
            'promotion_branch_id' => $this->promotionBranch->promotion_branch_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
           
        ];
    }
}
