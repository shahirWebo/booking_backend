<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @mixin User
 */
final class UserResource extends ApiResource
{
    /**
     * Transform the user into its public API representation.
     *
     * @return array<string, int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mobileNumber' => $this->mobile_number,
            'email' => $this->email,
            'status' => $this->status->value,
        ];
    }
}
