<?php
declare(strict_types=1);
namespace App\Mcp;
use NeuronAI\MCP\McpConnector;
use NeuronAI\MCP\McpTool;
use RuntimeException;
class McpPolicy
{
    public const READS = ['get_office_hours', 'list_locations'];
    public static int $factories = 0;
    public static function connector(array $flags = []): McpConnector
    {
        self::$factories++;
        return McpConnector::make([
            'command' => PHP_BINARY,
            'args' => [dirname(__DIR__, 2) . '/servers/policy-tools.php',
                ...$flags],
            'timeout' => 5,
        ]);
    }
    public static function select(array $allowed): array
    {
        if ($allowed === []) {
            return [];
        }
        $connector = self::connector(['--expanded']);
        $tools = $connector->only($allowed)->tools();
        self::assertExact($tools, $allowed);
        return $tools;
    }
    public static function names(array $tools): array
    {
        $names = array_map(fn ($tool) => $tool->getName(), $tools);
        sort($names);
        return $names;
    }
    public static function assertExact(array $tools, array $allowed): void
    {
        sort($allowed);
        if (self::names($tools) !== $allowed
            || count(array_unique($allowed)) !== count($allowed)) {
            throw new RuntimeException('Required MCP capability missing.');
        }
    }
    public static function approvalPreview(): array
    {
        return self::connector()->only(['maintenance_preview'])
            ->with('maintenance_preview',
                fn (McpTool $tool) => $tool->requireApproval(),
            )->tools();
    }
}
