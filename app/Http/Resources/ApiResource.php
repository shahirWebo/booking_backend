<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiResource extends JsonResource
{
    /**
     * ApiResponse owns the top-level API envelope.
     *
     * @var string|null
     */
    public static $wrap = null;
}
