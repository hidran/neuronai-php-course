<?php
declare(strict_types=1);
namespace App;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;
class HelloAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new OpenAI(
            key: $_ENV['OPENAI_KEY'],
            model: $_ENV['OPENAI_MODEL'] ?? 'gpt-6.1-sol',
        );
    }
    protected function instructions(): string
    {
        return 'You are a friendly PHP teacher. '
            . 'Answer in at most two short sentences.';
    }
}
