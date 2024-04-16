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
            //'username' => $this->user->username,
            //'user_email' => $this->user->email,
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
            'business_latitude' => $this->business_latitude,
            'business_longitude' => $this->business_longitude,
            'category_id' => $this->category_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            //'business_branch' => $this->businessBranch,
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
