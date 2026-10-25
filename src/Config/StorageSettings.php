<?php
declare(strict_types=1);
namespace App\Config;
final readonly class StorageSettings
{
    public function __construct(
        public string $chatStore,
        public string $path,
        public string $dsn,
        public ?string $user = null,
        public ?string $password = null,
    ) {
    }
    public static function fromEnv(array $env, string $root): self
    {
        $path = $env['CHAT_STORE_PATH'] ?? 'storage';
        return new self(
            chatStore: $env['CHAT_STORE'] ?? 'file',
            path: "{$root}/{$path}",
            dsn: $env['DB_DSN'] ?? "sqlite:{$root}/{$path}/chat.sqlite",
            user: $env['DB_USER'] ?? null,
            password: $env['DB_PASSWORD'] ?? null,
        );
    }
}
