<?php

declare(strict_types=1);

namespace App\Agents;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\Toolkits\ToolkitInterface;

class AssistantAgent extends Agent
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
        return (string) new SystemPrompt(
            background: [
                'You are a friendly assistant for PHP developers.',
                'You talk to developers who know the basics.',
            ],
            steps: [
                'Find the PHP feature behind the question.',
                'Prefer the modern way, from PHP 8 on.',
                'Write the shortest example that works.',
            ],
            output: [
                'Plain text only.',
                'Three lines, labeled Topic, Summary and Example.',
                'The summary is one short sentence.',
                'The example is one line of PHP.',
            ],
        );
    }

    /**
     * @return ToolInterface[]|ToolkitInterface[]
     */
    protected function tools(): array
    {
        return [];
    }

    /**
     * Attach middleware to nodes.
     */
    protected function middleware(): array
    {
        return [
            // ToolNode::class => [],
        ];
    }
}
