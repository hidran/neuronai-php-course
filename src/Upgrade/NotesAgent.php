<?php
declare(strict_types=1);
namespace App\Upgrade;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
// Plain PHP: new NotesAgent($provider, $store)->for($thread)
// Laravel: app(NotesAgent::class)->for($thread)
final class NotesAgent extends Agent
{
    public function __construct(
        AIProviderInterface $provider,
        private readonly MessageStoreInterface $store,
    ) {
        parent::__construct();
        $this->setAiProvider($provider);
    }
    #[\Override]
    protected function messageStore(): MessageStoreInterface
    {
        return $this->store;
    }
    #[\Override]
    protected function instructions(): SystemMessage
    {
        return new SystemMessage((string) new SystemPrompt(
            background: ['You keep short notes for a team.'],
        ));
    }
}
