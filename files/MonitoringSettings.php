<?php
declare(strict_types=1);
namespace App\Config;
use RuntimeException;
final readonly class MonitoringSettings
{
    public function __construct(public string $ingestionKey)
    {
    }
    /** @param array<string, mixed> $env */
    public static function fromEnv(array $env): self
    {
        $key = $env['INSPECTOR_INGESTION_KEY'] ?? '';
        if (!is_string($key) || $key === '') {
            throw new RuntimeException(
                'INSPECTOR_INGESTION_KEY is not set');
        }
        return new self($key);
    }
}
