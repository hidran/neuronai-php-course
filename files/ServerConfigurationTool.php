<?php
declare(strict_types=1);
namespace App\Tools;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
class ServerConfigurationTool extends Tool
{
    protected string $name = 'get_server_configuration';
    protected ?string $description = 'Read the teaching fixture '
        . 'for the training or backup server. Use for server addresses.';
    protected function properties(): array
    {
        return [new ToolProperty(
            name: 'server',
            type: PropertyType::STRING,
            description: 'The fixture name: training or backup.',
            required: true,
        )];
    }
    public function __invoke(string $server): string
    {
        $address = match ($server) {
            'training' => '192.0.2.42',
            'backup' => '192.0.2.43',
            default => throw new \InvalidArgumentException('Unknown fixture'),
        };
        return json_encode([
            'server' => $server,
            'address' => $address,
        ], JSON_THROW_ON_ERROR);
    }
}
