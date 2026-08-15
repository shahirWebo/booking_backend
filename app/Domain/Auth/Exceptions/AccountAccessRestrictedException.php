<?php

namespace App\Domain\Auth\Exceptions;

use App\Domain\Users\Enums\UserStatus;
use RuntimeException;

final class AccountAccessRestrictedException extends RuntimeException
{
    public function __construct(public readonly UserStatus $status)
    {
        parent::__construct('This account cannot access the API.');
    }
}
