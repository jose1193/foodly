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
        return [
            'username' => $this->user->first()->username,
            'user_email' => $this->user->first()->email,
            'business_id' => $this->id,
            'business_uuid' => $this->business_uuid,
            'business_name' => $this->business_name,
            'business_logo' => $this->business_logo,
            'business_email' => $this->business_email,
            'business_address' => $this->business_address,
            'business_zipcode' => $this->business_zipcode,
            'business_city' => $this->business_city,
            'business_country' => $this->business_country,
            'business_website' => $this->business_website,
            'business_opening_hours' => $this->business_opening_hours,
            'business_opening_date' => $this->business_opening_date,
            'business_latitude' => $this->business_latitude,
            'business_longitude' => $this->business_longitude,
            'category' => $this->category->first()->category_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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
