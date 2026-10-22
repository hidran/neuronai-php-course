<?php
declare(strict_types=1);
namespace App\Mcp;
use NeuronAI\Exceptions\ToolException;
use NeuronAI\MCP\McpConnector;
use NeuronAI\MCP\McpTool;
class HttpOfficeConnection
{
    public static function connector(?string $url = null): McpConnector
    {
        return McpConnector::make([
            'url' => $url ?? 'http://127.0.0.1:48392/mcp',
            'timeout' => 3,
        ])->only(['get_office_hours']);
    }
    public static function discover(?string $url = null): array
    {
        $started = hrtime(true);
        $tools = self::connector($url)->tools();
        $names = array_map(fn ($tool) => $tool->getName(), $tools);
        if ($names !== ['get_office_hours']) {
            throw new ToolException('Unexpected discovered capability.');
        }
        $tool = $tools[0];
        $schema = $tool->getInputSchema();
        if (!$tool instanceof McpTool
            || ($schema['properties']['location']['type'] ?? '') !== 'string'
            || ($schema['required'] ?? []) !== ['location']) {
            throw new ToolException('Unsupported office-hours schema.');
        }
        echo 'HTTP discovered: get_office_hours' . PHP_EOL;
        echo 'HTTP input: location (required string)' . PHP_EOL;
        echo 'HTTP discovery ms: ' . round((hrtime(true) - $started) / 1e6)
            . PHP_EOL;
        echo 'HTTP discovery ' . 'complete.' . PHP_EOL;
        return $tools;
    }
}
