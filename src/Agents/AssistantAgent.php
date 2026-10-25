<?php
declare(strict_types=1);
namespace App\Agents;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
final class AssistantAgent extends Agent
{
    public function __construct(AIProviderInterface $provider)
    {
        parent::__construct();
        $this->setAiProvider($provider);
    }
    #[\Override]
    protected function instructions(): SystemMessage
    {
        return new SystemMessage((string) new SystemPrompt(
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
        ));
    }
}
