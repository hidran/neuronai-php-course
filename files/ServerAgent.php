<?php
declare(strict_types=1);
namespace App\Agents;
use App\Tools\ServerConfigurationTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
final class ServerAgent extends Agent
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
            background: ['You answer questions about our servers.'],
            output: [
                'Give a short plain text answer with the addresses.',
            ],
            toolsUsage: [
                'Use the configuration tool for each server requested.',
                'Never invent addresses.',
            ],
        ));
    }
    #[\Override]
    protected function tools(): array
    {
        return [new ServerConfigurationTool()];
    }
}
