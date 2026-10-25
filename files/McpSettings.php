<?php
declare(strict_types=1);
namespace App\Config;
use RuntimeException;
final readonly class McpSettings
{
    public function __construct(
        public string $url,
        public string $token,
    ) {
    }
    /** @param array<string, mixed> $env */
    public static function fromEnv(array $env): self
    {
        return new self(
            url: $env['MCP_URL']
                ?? throw new RuntimeException('MCP_URL not set'),
            token: $env['MCP_TOKEN']
                ?? throw new RuntimeException('MCP_TOKEN is not set'),
        );
    }
}
