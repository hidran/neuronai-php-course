<?php
declare(strict_types=1);
namespace App\Tools;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Tools\PropertyType;
class FixtureWeatherTool extends Tool
{
    protected string $name = 'fixture_temperature';
    protected ?string $description = 'Teaching weather fixture. '
        . 'Get the fixture temperature in Celsius for a city.';
    protected function properties(): array
    {
        return [new ToolProperty(
            name: 'city', type: PropertyType::STRING,
            description: 'Turin or Milan', required: true,
        )];
    }
    public function __invoke(string $city): string
    {
        $values = ['Turin' => 18, 'Milan' => 22];
        return isset($values[$city])
            ? "Fixture {$city}: {$values[$city]} Celsius"
            : 'City unavailable in the teaching fixture.';
    }
}
