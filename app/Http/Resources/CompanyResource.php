<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'website' => $this->website,
            'contact_email' => $this->contact_email,
            'phone' => $this->phone,
            'city' => $this->city,
            'address' => $this->address,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
