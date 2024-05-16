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

    // Incluir los negocios del usuario
    $data['business'] = $this->businesses->map(function ($business) {
        // Incluir las imágenes de portada de los negocios
        $coverImages = $business->coverImages->map(function ($image) {
            return [
                'id' => $image->id,
                'business_id' => $image->business_id,
                'business_image_uuid' => $image->business_image_uuid,
                'business_image_path' => asset($image->business_image_path),
                // Incluir otros atributos de la imagen si los hay
            ];
        })->toArray();

        // Incluir las promociones de los negocios
        $promotions = $business->promotions->map(function ($promotion) {
            // Obtener las imágenes de promoción asociadas a esta promoción
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

        // Incluir las sucursales de los negocios
        $branch = $business->branches->isNotEmpty() ? $business->branches : [];

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
            'business_latitude' => $business->business_latitude,
            'business_longitude' => $business->business_longitude,
            'category_id' => $business->category_id,
            'category' => $business->category,
            'business_cover_images' => $coverImages,
            'business_promotions' => $promotions, 
            'business_branch' => $branch,
        ];
    })->toArray(); // Convertir la colección en un array

    return $data;
}


}

