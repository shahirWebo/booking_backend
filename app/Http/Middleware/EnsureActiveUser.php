<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Exceptions\AccountAccessRestrictedException;
use App\Domain\Auth\Services\AuthenticationAuditLogger;
use App\Domain\Users\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveUser
{
    public function __construct(private readonly AuthenticationAuditLogger $auditLogger) {}

    /**
     * Reject bearer-token access for accounts that are no longer active.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->status !== UserStatus::Active) {
            $user->currentAccessToken()->delete();
            $this->auditLogger->sessionRevokedForRestrictedUser($user, $user->status);

            throw new AccountAccessRestrictedException($user->status);
        }

        return $next($request);
    }
}
