<?php
declare(strict_types=1);
namespace App\Agents;
use App\Tools\FixtureWeatherTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\Toolkits\Calculator\MeanTool;
final class ObservedWeatherAgent extends Agent
{
    public function __construct(
        AIProviderInterface $provider,
        FixtureWeatherTool $weather = new FixtureWeatherTool(),
    ) {
        parent::__construct();
        $this->setAiProvider($provider);
        $this->addTool([$weather, MeanTool::make()]);
    }
    #[\Override]
    protected function instructions(): SystemMessage
    {
        return new SystemMessage((string) new SystemPrompt(
            background: ['A teaching weather fixture, not live data.'],
            steps: [
                'Call fixture_temperature once per requested city.',
                'Call mean with both returned temperatures.',
            ],
            output: ['Report the fixture values and average in Celsius.'],
        ));
    }
}
