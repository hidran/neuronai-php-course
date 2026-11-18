<?php
declare(strict_types=1);
namespace App\Agents;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
final class ReportFormatter extends Agent
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
                'You write the final report of a PHP project audit.',
                'You receive the evidence log and the rubric result.',
            ],
            steps: [
                'Copy the rubric score exactly.',
                'Write one finding per rubric penalty, with its '
                    . 'category, citing its evidence id.',
                'Add other findings only when the evidence shows them.',
                'Unavailable evidence stays unavailable: never guess.',
                'List every evidence item as a check with its id '
                    . 'and its exact status.',
            ],
            output: [
                'Evidence ids must exist in the evidence log.',
                'Descriptions and recommendations: at most 25 words.',
            ],
        ));
    }
}
