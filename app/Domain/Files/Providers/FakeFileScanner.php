<?php

namespace App\Domain\Files\Providers;

use App\Domain\Files\Contracts\FileScanner;

final class FakeFileScanner implements FileScanner
{
    public function isClean(string $contents): bool
    {
        return true;
    }
}
