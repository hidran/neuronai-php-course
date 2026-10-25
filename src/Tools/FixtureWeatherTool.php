<?php
declare(strict_types=1);
namespace App\Tools;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
final class FixtureWeatherTool extends Tool
{
    private const array VALUES = ['Turin' => 18, 'Milan' => 22];
    protected string $name = 'fixture_temperature';
    protected ?string $description = 'Teaching weather fixture. '
        . 'Get the fixture temperature in Celsius for a city.';
    public function __construct(?string $description = null)
    {
        $this->description = $description ?? $this->description;
    }
    #[\Override]
    protected function properties(): array
    {
        return [new ToolProperty(
            name: 'city',
            type: PropertyType::STRING,
            description: 'Turin or Milan',
            required: true,
        )];
    }
    public function __invoke(string $city): string
    {
        $value = self::VALUES[$city] ?? null;
        return $value === null
            ? 'City unavailable in the teaching fixture.'
            : "Fixture {$city}: {$value} Celsius";
    }
}
