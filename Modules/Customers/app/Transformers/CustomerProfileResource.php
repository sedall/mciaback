<?php

namespace Modules\Customers\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'national_code' => $this->national_code,
            'birth_date' => $this->birth_date,
            'father_name' => $this->father_name,
            'postal_code' => $this->postal_code,
            'address' => $this->address,
            'kyc_status' => $this->kyc_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
