<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionBranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'promotion_branch_uuid' => $this->promotion_branch_uuid,
            'promotion_branch_title' => $this->promotion_branch_title,
            'promotion_branch_description' => $this->promotion_branch_description,
            'promotion_branch_start_date' => $this->promotion_branch_start_date,
            'promotion_branch_end_date' => $this->promotion_branch_end_date,
            'promotion_branch_type' => $this->promotion_branch_type,
            'promotion_branch_status' => $this->promotion_branch_status,
            'branch_id' => $this->branches ? $this->branches->branch_id : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'delete_at' => $this->delete_at,
            'business_branch' => new BranchResource($this->branches),
            'promotions_branches_images' => PromotionBranchImageResource::collection($this->promotionBranchesImages), 
        ];
    }
}
