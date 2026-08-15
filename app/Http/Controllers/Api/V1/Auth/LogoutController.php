<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Services\AuthenticationAuditLogger;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class LogoutController extends Controller
{
    /**
     * Revoke only the bearer credential used for this request.
     */
    public function __invoke(Request $request, AuthenticationAuditLogger $auditLogger): Response
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $auditLogger->sessionLoggedOut($user);

        return ApiResponse::noContent();
    }
}
