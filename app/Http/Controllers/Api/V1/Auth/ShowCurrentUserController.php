<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShowCurrentUserController extends Controller
{
    /**
     * Return the profile for the user authenticated by the bearer token.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }
}
