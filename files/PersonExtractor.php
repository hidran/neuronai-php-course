<?php
declare(strict_types=1);
namespace App\Agents;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
final class PersonExtractor extends Agent
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
            background: ['You extract people from the supplied text.'],
            steps: ['Use only facts explicitly stated.'],
        ));
    }
}
