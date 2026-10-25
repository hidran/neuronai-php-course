<?php
declare(strict_types=1);
namespace App\Deterministic;
use ArrayObject;
final readonly class StubWeatherClient implements WeatherClient
{
    /** @param array<string, mixed> $response */
    public function __construct(
        private ArrayObject $calls,
        private array $response = ['temperature' => 14],
    ) {
    }
    #[\Override]
    public function current(float $latitude, float $longitude): array
    {
        $this->calls[] = [$latitude, $longitude];
        return $this->response;
    }
}
