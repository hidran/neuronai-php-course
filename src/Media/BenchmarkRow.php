<?php
declare(strict_types=1);
namespace App\Media;
final readonly class BenchmarkRow
{
    /** @param array<string, bool> $checks */
    public function __construct(
        public string $variant,
        public string $thread,
        public Measurement $measurement,
        public ?int $inputTokens,
        public float $seconds,
        public CostProbe $probe,
        public array $checks,
    ) {
    }
    public function passed(): bool
    {
        return $this->checks !== []
            && !in_array(false, $this->checks, true);
    }
}
