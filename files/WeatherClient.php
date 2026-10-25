<?php
declare(strict_types=1);
namespace App\Deterministic;
interface WeatherClient
{
    public function current(float $latitude, float $longitude): array;
}
