<?php

namespace App\Domain\Files\Providers;

use App\Domain\Files\Contracts\FileScanner;
use RuntimeException;

final class UnavailableFileScanner implements FileScanner
{
    public function isClean(string $contents): bool
    {
        throw new RuntimeException('No file scanner is configured.');
    }
}
