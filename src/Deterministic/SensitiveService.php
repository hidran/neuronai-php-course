<?php
declare(strict_types=1);
namespace App\Deterministic;
use RuntimeException;
final readonly class SensitiveService
{
    public function read(bool $authorized): string
    {
        if (!$authorized) {
            throw new RuntimeException('Access denied.');
        }
        return 'Teaching fixture only';
    }
}
