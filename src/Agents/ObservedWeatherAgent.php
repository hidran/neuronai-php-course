<?php
declare(strict_types=1);
namespace App\Agents;
use App\Tools\FixtureWeatherTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Tools\Toolkits\Calculator\MeanTool;
class ObservedWeatherAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Anthropic(
            key: $_ENV['ANTHROPIC_KEY'],
            model: $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-5-5',
        );
    }
    protected function instructions(): string
    {
        return 'This is a teaching weather fixture, not live data. '
            . 'Call fixture_temperature once for each requested city. '
            . 'Call mean with both returned temperatures. '
            . 'Report the fixture values and average in Celsius.';
    }
    protected function tools(): array
    {
        return [new FixtureWeatherTool(), MeanTool::make()];
    }
}
