<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerProfileResource;
use App\Models\CustomerProfile;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = CustomerProfile::query()->firstOrCreate([
            'user_id' => $request->user()->id,
        ])->load('user');

        return ApiResponse::success(new CustomerProfileResource($profile));
    }
}
