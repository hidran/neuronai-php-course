<?php
declare(strict_types=1);
namespace App\Agents;
use App\Tools\ServerConfigurationTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
class ServerAgent extends Agent
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
        return 'Use the configuration tool for each server requested. '
            . 'Never invent addresses. Give a short plain-text answer '
            . 'using the returned fixture addresses.';
    }
    protected function tools(): array
    {
        return [new ServerConfigurationTool()];
    }
}
