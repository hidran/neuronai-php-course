<?php
declare(strict_types=1);
namespace App\Mcp;
use NeuronAI\MCP\McpConnector;
use NeuronAI\MCP\McpTool;
use RuntimeException;
class OfficeHoursConnection
{
    public static function connector(): McpConnector
    {
        // Configure the stdio connector here.
    }
    public static function discover(): array
    {
        $started = hrtime(true);
        $tools = self::connector()->tools();
        $names = array_map(fn ($tool) => $tool->getName(), $tools);
        if ($names !== ['get_office_hours']) {
            throw new RuntimeException('Unexpected discovery list.');
        }
        $tool = $tools[0];
        $schema = $tool->getInputSchema();
        if (!$tool instanceof McpTool
            || ($schema['properties']['location']['type'] ?? '') !== 'string'
            || ($schema['required'] ?? []) !== ['location']) {
            throw new RuntimeException('Unexpected converted schema.');
        }
        echo 'Discovered: ' . $tool->getName() . PHP_EOL;
        echo 'Input: location (required string)' . PHP_EOL;
        echo 'Tool class: ' . $tool::class . PHP_EOL;
        echo 'Discovery ms: ' . round((hrtime(true) - $started) / 1e6)
            . PHP_EOL;
        echo 'Discovery check complete.' . PHP_EOL;
        return $tools;
    }
}
