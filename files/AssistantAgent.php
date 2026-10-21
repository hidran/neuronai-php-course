<?php

declare(strict_types=1);

namespace App\Agents;

use Composer\InstalledVersions;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\Toolkits\ToolkitInterface;

class AssistantAgent extends Agent
{
    private string $providerName = 'anthropic';

    public function useProvider(string $name): static
    {
        $this->providerName = $name;
        return $this;
    }

    protected function provider(): AIProviderInterface
    {
        return match ($this->providerName) {
            'anthropic' => new Anthropic(
                key: $_ENV['ANTHROPIC_KEY'],
                model: $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-5-5',
            ),
            'openai' => new OpenAI(
                key: $_ENV['OPENAI_KEY'],
                model: $_ENV['OPENAI_MODEL'] ?? 'gpt-6.1-sol',
            ),
            'gemini' => new Gemini(
                key: $_ENV['GEMINI_KEY'],
                model: $_ENV['GEMINI_MODEL'] ?? 'gemini-3.6-flash',
            ),
            'ollama' => new Ollama(
                url: $_ENV['OLLAMA_URL'] ?? 'http://localhost:11434/api',
                model: $_ENV['OLLAMA_MODEL'] ?? 'qwen2.5:7b',
            ),
        };
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

    protected function context(): array
    {
        $version = InstalledVersions::getPrettyVersion(
            'neuron-core/neuron-ai',
        );
        return [
            new TextContent('Now: ' . date('Y-m-d H:i:s T')),
            new TextContent("Installed NeuronAI: {$version}"),
        ];
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
