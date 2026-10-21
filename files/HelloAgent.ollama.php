<?php
declare(strict_types=1);
namespace App;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
class HelloAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Ollama(
            url: $_ENV['OLLAMA_URL'] ?? 'http://localhost:11434/api',
            model: $_ENV['OLLAMA_MODEL'] ?? 'qwen2.5:7b',
        );
    }
    protected function instructions(): string
    {
        return 'You are a friendly PHP teacher. '
            . 'Answer in at most two short sentences.';
    }
}
