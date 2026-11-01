<?php
declare(strict_types=1);
namespace App\Neuron\Agents;
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
                'You are the support assistant of a small software shop.',
                'Support is open Monday to Friday, 9:00 to 17:00 CET.',
            ],
            steps: [
                'Answer the question in one or two short sentences.',
                'If you do not know, say so and suggest emailing support.',
            ],
            output: ['Plain text only, no markdown.'],
        ));
    }
}
