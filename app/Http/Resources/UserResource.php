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
            'date_of_birth' => $this->date_of_birth,
            'phone' => $this->phone,
            'google_id' => $this->google_id,
            'address' => $this->address,
            'zip_code' => $this->zip_code,
            'city' => $this->city,
            'country' => $this->country,
            'gender' => $this->gender,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user_role' => $this->roles->pluck('name')->first() ?? null,
            'role_id' => $this->roles->pluck('id')->first() ?? null,
        ];

        // Verificar si el usuario tiene negocios asociados
        if ($this->businesses->isNotEmpty()) {
            $data['business'] = $this->businesses->map(function ($business) {
                return [
                    'id' => $business->id,
                    'user_id' => $business->user_id,
                    'business_uuid' => $business->business_uuid,
                    'business_logo' => $business->business_logo,
                    'business_name' => $business->business_name,
                    'business_email' => $business->business_email,
                    'business_phone' => $business->business_phone,
                    'business_address' => $business->business_address,
                    'business_zipcode' => $business->business_zipcode,
                    'business_city' => $business->business_city,
                    'business_country' => $business->business_country,
                    'business_website' => $business->business_website,
                    'business_latitude' => $business->business_latitude,
                    'business_longitude' => $business->business_longitude,
                    'category_id' => $business->category_id,
                    'cover_images' => $business->coverImages->pluck('business_image_path')->toArray(),
                ];
            })->toArray(); // Convertir la colección en un array
        } else {
            // Si el usuario no tiene negocios asociados, incluir los campos con valor null
            $data['business'] = [];
        }

        return $data;
    }
}

