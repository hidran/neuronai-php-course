<?php
declare(strict_types=1);
namespace App\Agents;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
final class TeachingAgent extends Agent
{
    public function __construct(
        AIProviderInterface $provider,
        private readonly ?SystemMessage $prompt = null,
    ) {
        parent::__construct();
        $this->setAiProvider($provider);
    }
    #[\Override]
    protected function instructions(): SystemMessage
    {
        return $this->prompt ?? new SystemMessage((string)
        new SystemPrompt(
            background: ['You explain PHP concepts accurately.'],
            steps: ['Ask for clarification when a question is ambiguous.'],
            output: [
                'One short paragraph.',
                'For unrelated topics, say you only teach PHP.',
            ],
        ));
    }
}
