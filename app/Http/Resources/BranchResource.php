<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'branch_id' => $this->id,
            'branch_uuid' => $this->branch_uuid,
            'branch_logo' => asset($this->branch_logo),
            'branch_name' => $this->branch_name,
            'branch_email' => $this->branch_email,
            'branch_phone' => $this->branch_phone,
            'branch_address' => $this->branch_address,
            'branch_zipcode' => $this->branch_zipcode,
            'branch_city' => $this->branch_city,
            'branch_country' => $this->branch_country,
            'branch_website' => $this->branch_website,
            'branch_latitude' => $this->branch_latitude,
            'branch_longitude' => $this->branch_longitude,
            'business_id' => $this->business_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            //'business' => $this->business,
        ];
    }
}
