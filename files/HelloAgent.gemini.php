<?php
declare(strict_types=1);
namespace App;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Gemini\Gemini;
class HelloAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Gemini(
            key: $_ENV['GEMINI_KEY'],
            model: $_ENV['GEMINI_MODEL'] ?? 'gemini-3.6-flash',
        );
    }
    protected function instructions(): string
    {
        return 'You are a friendly PHP teacher. '
            . 'Answer in at most two short sentences.';
    }
}
