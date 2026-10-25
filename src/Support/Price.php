<?php
declare(strict_types=1);
namespace App\Support;
final readonly class Price
{
    public function __construct(
        public float $inputPerMillion,
        public float $outputPerMillion,
    ) {
    }
    public function of(int $inputTokens, int $outputTokens): float
    {
        return ($inputTokens * $this->inputPerMillion
            + $outputTokens * $this->outputPerMillion) / 1_000_000;
    }
}
