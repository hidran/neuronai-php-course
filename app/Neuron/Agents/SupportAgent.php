<?php
declare(strict_types=1);
namespace App\Neuron\Agents;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Workflow\Persistence\PersistenceInterface;
final class SupportAgent extends Agent
{
    public function __construct(
        AIProviderInterface $provider,
        private readonly MessageStoreInterface $conversations,
        private readonly PersistenceInterface $runs,
    ) {
        parent::__construct();
        $this->setAiProvider($provider);
    }
    #[\Override]
    protected function messageStore(): MessageStoreInterface
    {
        return $this->conversations;
    }
    #[\Override]
    protected function persistence(): PersistenceInterface
    {
        return $this->runs;
    }
    #[\Override]
    protected function instructions(): SystemMessage
    {
        return new SystemMessage((string) new SystemPrompt(
            background: [
                'You support the customers of a small software shop.',
                'You answer inside one customer conversation.',
            ],
            steps: [
                'Answer in one or two short sentences.',
                'If unsure, say so and suggest emailing support.',
            ],
            output: ['Plain text only, no markdown.'],
        ));
    }
}
