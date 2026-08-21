<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerProfileResource;
use App\Models\CustomerProfile;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CustomerProfileController extends Controller
{
    public function show(Request $request): JsonResponse|InertiaResponse
    {
        $profile = CustomerProfile::query()->firstOrCreate([
            'user_id' => $request->user()->id,
        ])->load('user');

        $resource = new CustomerProfileResource($profile);

        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiResponse::success($resource);
        }

        return Inertia::render('customer/Profile', [
            'profile' => $resource->resolve($request),
        ]);
    }
}
