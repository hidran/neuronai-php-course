<?php
declare(strict_types=1);
namespace App\Observability;
use NeuronAI\Chat\Messages\Usage;
final readonly class Prices
{
    /** @param array<string, array{input: float, output: float}> $perMillion
     */
    public function __construct(private array $perMillion)
    {
    }
    public function costUsd(string $model, ?Usage $usage): ?float
    {
        $price = $this->perMillion[$model] ?? null;
        if ($price === null || $usage === null) {
            return null;
        }
        return round(($usage->inputTokens * $price['input']
            + $usage->outputTokens * $price['output']) / 1_000_000, 6);
    }
}
