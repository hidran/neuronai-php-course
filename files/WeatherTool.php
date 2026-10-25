<?php
declare(strict_types=1);
namespace App\Deterministic;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use RuntimeException;
final class WeatherTool extends Tool
{
    protected string $name = 'get_current_weather';
    protected ?string $description = 'Current temperature in Celsius.';
    public function __construct(private readonly WeatherClient $client)
    {
    }
    #[\Override]
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'latitude',
                type: PropertyType::NUMBER,
                description: 'Latitude in degrees',
                required: true,
            ),
            new ToolProperty(
                name: 'longitude',
                type: PropertyType::NUMBER,
                description: 'Longitude in degrees',
                required: true,
            ),
        ];
    }
    public function __invoke(float $latitude, float $longitude): string
    {
        $data = $this->client->current($latitude, $longitude);
        if (!isset($data['temperature'])) {
            throw new RuntimeException('Temperature unavailable.');
        }
        return $data['temperature'] . ' Celsius';
    }
}
