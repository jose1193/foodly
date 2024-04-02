<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'category_name' => $this->category_name,
            'category_description' => $this->category_description,
            'url_icon' => $this->url_icon,
            'bgcolor' => $this->bgcolor,
            'user' => $this->user->first()->username,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
