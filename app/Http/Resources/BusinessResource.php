<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
{
     $cover_images = $this->coverImages()->exists() ? $this->coverImages->map(function ($image) {
        return [
            'id' => $image->id,
            'business_image_uuid' => $image->business_image_uuid,
            'business_image_path' => asset($image->business_image_path),
            'business_id' => $image->business_id,
            'created_at' => $image->created_at->toDateTimeString(),  
            'updated_at' => $image->updated_at->toDateTimeString(),
        ];
    })->toArray() : [];

     $branches = $this->branches()->exists() ? $this->branches->map(function ($branch) {
        return [
            'id' => $branch->id,
            'branch_uuid' => $branch->branch_uuid,
            'branch_logo' => asset($branch->branch_logo),
            'branch_name' => $branch->branch_name,
            'branch_email' => $branch->branch_email,
            'branch_phone' => $branch->branch_phone,
            'business_address' => $branch->business_address,
            'branch_zipcode' => $branch->branch_zipcode,
            'branch_city' => $branch->branch_city,
            'branch_country' => $branch->branch_country,
            'branch_website' => $branch->branch_website,
            'branch_latitude' => $branch->branch_latitude,
            'branch_longitude' => $branch->branch_longitude,
            'business_id' => $branch->business_id,
            'deleted_at' => $branch->deleted_at,
            'created_at' => $branch->created_at ? $branch->created_at->toDateTimeString() : null,
            'updated_at' => $branch->updated_at ? $branch->updated_at->toDateTimeString() : null,
        ];
    })->toArray() : [];

    return [
        'user_id' => $this->user_id,
        'business_id' => $this->id,
        'business_uuid' => $this->business_uuid,
        'business_logo' => asset($this->business_logo),
        'business_name' => $this->business_name,
        'business_email' => $this->business_email,
        'business_phone' => $this->business_phone,
        'business_address' => $this->business_address,
        'business_zipcode' => $this->business_zipcode,
        'business_city' => $this->business_city,
        'business_country' => $this->business_country,
        'business_website' => $this->business_website,
        'business_about_us' => $this->business_about_us,
        'business_services' => $this->business_services,
        'business_additional_info' => $this->business_additional_info,
        'business_latitude' => $this->business_latitude,
        'business_longitude' => $this->business_longitude,
        'category_id' => $this->category ? $this->category->id : null,
        'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
        'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,

        'cover_images' => $cover_images,
        'branches' => $branches,
    ];
}

    
        //protected function getBusinessPhoto()
        // {
        // Obtener la primera imagen de portada asociada al negocio
       // $coverImage = $this->coverImages->first();

        // Si existe una imagen de portada, retornar su ruta
        //if ($coverImage) {
           // return $coverImage->business_image_path;
        //}

        // Si no hay una imagen de portada, retornar null
       // return null;
        // }
}
