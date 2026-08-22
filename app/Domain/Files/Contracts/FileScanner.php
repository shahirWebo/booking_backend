<?php

namespace App\Domain\Files\Contracts;

interface FileScanner
{
    /**
     * Return false when the bytes are known to be unsafe.
     *
     * Implementations must throw when they cannot determine safety.
     */
    public function isClean(string $contents): bool;
}
