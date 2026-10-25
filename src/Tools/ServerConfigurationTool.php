<?php
declare(strict_types=1);
namespace App\Tools;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
final class ServerConfigurationTool extends Tool
{
    private const array ADDRESSES = [
        'training' => '192.0.2.42',
        'backup' => '192.0.2.43',
    ];
    protected string $name = 'get_server_configuration';
    protected ?string $description = 'Read the teaching fixture '
        . 'for the training or backup server. Use it for addresses.';
    #[\Override]
    protected function properties(): array
    {
        return [new ToolProperty(
            name: 'server',
            type: PropertyType::STRING,
            description: 'The fixture name: training or backup.',
            required: true,
            enum: array_keys(self::ADDRESSES),
        )];
    }
    public function __invoke(string $server): string
    {
        $address = self::ADDRESSES[$server]
            ?? throw new \InvalidArgumentException("Unknown {$server}");
        return json_encode([
            'server' => $server,
            'address' => $address,
        ], JSON_THROW_ON_ERROR);
    }
}
