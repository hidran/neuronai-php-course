<?php
declare(strict_types=1);
namespace App\Neuron\Agents;
use App\Neuron\Tools\OfficeHours;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
final class SupportAgent extends Agent
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
            background: ['You answer support questions.'],
            steps: ['Use the office_hours tool for opening times.'],
            output: ['Plain text, at most two sentences.'],
        ));
    }
    #[\Override]
    protected function tools(): array
    {
        return [new OfficeHours()];
    }
}
