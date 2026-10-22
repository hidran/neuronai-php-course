<?php
declare(strict_types=1);
namespace App\Agents;
use App\Mcp\OfficeHoursConnection;
use App\PackageVersionTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
class OfficeHoursAgent extends Agent
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
        return 'Use get_office_hours for every office-hours question. '
            . 'Do not guess. Report exact returned hours and timezone '
            . 'in a short plain-text answer.';
    }
    protected function tools(): array
    {
        // Return the flat local and discovered tool list here.
    }
}
