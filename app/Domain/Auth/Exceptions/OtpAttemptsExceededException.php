<?php

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class OtpAttemptsExceededException extends RuntimeException {}
