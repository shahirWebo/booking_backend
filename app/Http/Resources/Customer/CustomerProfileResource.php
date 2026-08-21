<?php

namespace App\Http\Resources\Customer;

use App\Http\Resources\ApiResource;
use App\Models\CustomerProfile;
use Illuminate\Http\Request;

/**
 * @mixin CustomerProfile
 */
final class CustomerProfileResource extends ApiResource
{
    /**
     * Transform the customer profile into its public API representation.
     *
     * @return array<string, int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user?->name,
            'mobile_number' => $this->user?->mobile_number,
            'email' => $this->user?->email,
        ];
    }
}
