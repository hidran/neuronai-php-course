<?php
declare(strict_types=1);
namespace App\Config;
use RuntimeException;
final readonly class VectorStoreSettings
{
    private const array KINDS = ['memory', 'file'];
    public function __construct(
        public string $kind,
        public string $directory,
        public int $topK,
    ) {
    }
    /** @param array<string, mixed> $env */
    public static function fromEnv(array $env, string $root): self
    {
        $kind = (string) ($env['VECTOR_STORE'] ?? 'memory');
        if (!in_array($kind, self::KINDS, true)) {
            throw new RuntimeException("Unknown VECTOR_STORE {$kind}");
        }
        $path = (string) ($env['VECTOR_STORE_PATH']
            ?? 'storage/vectors');
        return new self(
            kind: $kind,
            directory: $root . '/' . $path,
            topK: (int) ($env['VECTOR_STORE_TOP_K'] ?? 4),
        );
    }
}
