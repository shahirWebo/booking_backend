<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\LogoutAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class LogoutController extends Controller
{
    /**
     * Revoke only the bearer credential used for this request.
     */
    public function __invoke(Request $request, LogoutAction $logout): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $logout->execute($user);

        return ApiResponse::noContent();
    }
}
