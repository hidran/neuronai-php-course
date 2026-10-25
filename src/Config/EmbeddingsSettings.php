<?php
declare(strict_types=1);
namespace App\Config;
use RuntimeException;
final readonly class EmbeddingsSettings
{
    public function __construct(
        public EmbeddingsProvider $provider,
        public string $model,
        public ?int $dimensions = null,
        public ?string $key = null,
        public ?string $url = null,
    ) {
    }
    /** @param array<string, mixed> $env */
    public static function fromEnv(array $env): self
    {
        $name = $env['EMBEDDINGS_PROVIDER'] ?? 'ollama';
        $provider = EmbeddingsProvider::tryFrom((string) $name)
            ?? throw new RuntimeException(
                "Unknown EMBEDDINGS_PROVIDER {$name}");
        $model = $env['EMBEDDINGS_MODEL'] ?? '';
        if ($model === '') {
            throw new RuntimeException('EMBEDDINGS_MODEL is not set');
        }
        $prefix = $provider->prefix();
        $key = $env["{$prefix}_KEY"] ?? null;
        if ($provider->needsKey() && ($key === null || $key === '')) {
            throw new RuntimeException("{$prefix}_KEY is not set");
        }
        $dimensions = $env['EMBEDDINGS_DIMENSIONS'] ?? null;
        return new self(
            provider: $provider,
            model: (string) $model,
            dimensions: $dimensions === null || $dimensions === ''
                ? null : (int) $dimensions,
            key: $provider->needsKey() ? (string) $key : null,
            url: $env["{$prefix}_URL"] ?? null,
        );
    }
}
