<?php
declare(strict_types=1);
namespace App\Config;
use RuntimeException;
final readonly class Settings
{
    public function __construct(
        public Provider $provider,
        public string $model,
        public ?string $key = null,
        public ?string $url = null,
    ) {
    }
    public static function fromEnv(array $env): self
    {
        $name = $env['NEURON_PROVIDER'] ?? 'anthropic';
        $provider = Provider::tryFrom($name)
            ?? throw new RuntimeException(
                "Unknown NEURON_PROVIDER {$name}"
            );
        $prefix = $provider->prefix();
        $key = $env["{$prefix}_KEY"] ?? null;
        if ($provider->needsKey() && ($key === null || $key === '')) {
            throw new RuntimeException("{$prefix}_KEY is not set");
        }
        return new self(
            provider: $provider,
            model: $env["{$prefix}_MODEL"]
                ?? throw new RuntimeException(
                    "{$prefix}_MODEL is not set"
                ),
            key: $key,
            url: $env["{$prefix}_URL"] ?? null,
        );
    }
}
