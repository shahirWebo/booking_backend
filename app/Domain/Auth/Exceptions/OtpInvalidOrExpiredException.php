<?php

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class OtpInvalidOrExpiredException extends RuntimeException {}
