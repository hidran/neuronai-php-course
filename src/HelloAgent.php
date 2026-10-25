<?php
declare(strict_types=1);
namespace App;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
final class HelloAgent extends Agent
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
            background: ['You are a friendly PHP teacher.'],
            output: ['Answer in at most two short sentences.'],
        ));
    }
    #[\Override]
    protected function tools(): array
    {
        return [new PackageVersionTool()];
    }
}
