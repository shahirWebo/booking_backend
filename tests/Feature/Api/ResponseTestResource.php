<?php

namespace Tests\Feature\Api;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

final class ResponseTestResource extends ApiResource
{
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'name' => $this->resource['name'],
        ];
    }
}
