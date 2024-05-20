<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'photo' => $this->profile_photo_url,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'date_of_birth' => $this->date_of_birth,
            'phone' => $this->phone,
            'address' => $this->address,
            'zip_code' => $this->zip_code,
            'city' => $this->city,
            'country' => $this->country,
            'gender' => $this->gender,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'user_role' => $this->roles->pluck('name')->first() ?? null,
            'role_id' => $this->roles->pluck('id')->first() ?? null,
            'social_provider' => $this->providers->isNotEmpty() ? $this->providers : [],
        ];

        // Include the user's businesses
        $data['business'] = $this->businesses->map(function ($business) {
            // Include business cover images
            $coverImages = $business->coverImages->map(function ($image) {
                return [
                    'id' => $image->id,
                    'business_id' => $image->business_id,
                    'business_image_uuid' => $image->business_image_uuid,
                    'business_image_path' => asset($image->business_image_path),
                ];
            })->toArray();

            // Include business promotions
            $promotions = $business->promotions->map(function ($promotion) {
                $promotionImages = $promotion->promotionImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'promotion_image_uuid' => $image->promotion_image_uuid,
                        'promotion_image_path' => asset($image->promotion_image_path),
                        'promotion_id' => $image->promotion_id,
                    ];
                })->toArray();

                return [
                    'id' => $promotion->id,
                    'promotion_uuid' => $promotion->promotion_uuid,
                    'promotion_title' => $promotion->promotion_title,
                    'promotion_description' => $promotion->promotion_description,
                    'promotion_start_date' => $promotion->promotion_start_date,
                    'promotion_end_date' => $promotion->promotion_end_date,
                    'promotion_type' => $promotion->promotion_type,
                    'promotion_status' => $promotion->promotion_status,
                    'business_id' => $promotion->business_id,
                    'promotion_images' => $promotionImages,
                ];
            })->toArray();

            // Include business branches
            $branches = $business->branches->map(function ($branch) {
                // Include branch cover images
                $branchCoverImages = $branch->coverImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'branch_id' => $image->branch_id,
                        'branch_image_uuid' => $image->branch_image_uuid,
                        'branch_image_path' => asset($image->branch_image_path),
                    ];
                })->toArray();

                $branchPromotions = $branch->promotionsbranches->map(function ($branchPromotion) {
                    $branchPromotionImages = $branchPromotion->promotionBranchesImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'promotion_branch_image_uuid' => $image->promotion_branch_image_uuid,
                            'promotion_branch_image_path' => asset($image->promotion_branch_image_path),
                            'promotion_branch_id' => $image->promotion_branch_id,
                        ];
                    })->toArray();

                    return [
                        'id' => $branchPromotion->id,
                        'promotion_branch_uuid' => $branchPromotion->promotion_branch_uuid,
                        'promotion_branch_title' => $branchPromotion->promotion_branch_title,
                        'promotion_branch_description' => $branchPromotion->promotion_branch_description,
                        'promotion_start_date' => $branchPromotion->promotion_start_date,
                        'promotion_end_date' => $branchPromotion->promotion_end_date,
                        'promotion_type' => $branchPromotion->promotion_type,
                        'promotion_status' => $branchPromotion->promotion_status,
                        'branch_id' => $branchPromotion->branch_id,
                        'branch_promotion_images' => $branchPromotionImages,
                    ];
                })->toArray();

                return [
                    'id' => $branch->id,
                    'branch_uuid' => $branch->branch_uuid,
                    'branch_logo' => asset($branch->branch_logo),
                    'branch_name' => $branch->branch_name,
                    'branch_email' => $branch->branch_email,
                    'branch_phone' => $branch->branch_phone,
                    'branch_address' => $branch->branch_address,
                    'branch_zipcode' => $branch->branch_zipcode,
                    'branch_city' => $branch->branch_city,
                    'branch_country' => $branch->branch_country,
                    'branch_website' => $branch->branch_website,
                    'branch_latitude' => (double) $branch->branch_latitude,
                    'branch_longitude' => (double) $branch->branch_longitude,
                    'business_id' => (int) $branch->business_id,
                    'branch_cover_images' => $branchCoverImages,
                    'branch_promotions' => $branchPromotions,
                ];
            })->toArray();

            return [
                'id' => $business->id,
                'user_id' => $business->user_id,
                'business_uuid' => $business->business_uuid,
                'business_logo' => asset($business->business_logo),
                'business_name' => $business->business_name,
                'business_email' => $business->business_email,
                'business_phone' => $business->business_phone,
                'business_address' => $business->business_address,
                'business_zipcode' => $business->business_zipcode,
                'business_city' => $business->business_city,
                'business_country' => $business->business_country,
                'business_website' => $business->business_website,
                'business_about_us' => $business->business_about_us,
                'business_services' => $business->business_services,
                'business_additional_info' => $business->business_additional_info,
                'business_latitude' => (double) $business->business_latitude,
                'business_longitude' => (double) $business->business_longitude,
                'category_id' => $business->category ? $business->category->id : null,
                'category' => $business->category,
                'cover_images' => $coverImages,
                'business_promotions' => $promotions,
                'business_branches' => $branches,
            ];
        })->toArray();

        return $data;
    }
}



