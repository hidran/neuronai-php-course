<?php
declare(strict_types=1);
namespace App\Agents;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
class StreamingAgent extends Agent
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
        return 'You are a PHP teacher. Use plain text. '
            . 'Answer in four short sentences, without tools.';
    }
}
