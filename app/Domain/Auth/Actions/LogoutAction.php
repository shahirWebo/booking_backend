<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Repositories\UserRepository;
use App\Domain\Auth\Services\AuthenticationAuditLogger;
use App\Models\User;

final class LogoutAction
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthenticationAuditLogger $auditLogger,
    ) {}

    public function execute(User $user): void
    {
        $this->users->revokeCurrentAccessToken($user);
        $this->auditLogger->sessionLoggedOut($user);
    }
}
