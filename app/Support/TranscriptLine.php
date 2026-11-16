<?php
declare(strict_types=1);
namespace App\Support;
final readonly class TranscriptLine
{
    public function __construct(
        public string $role,
        public string $text,
    ) {
    }
}
