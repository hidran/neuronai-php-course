<?php
declare(strict_types=1);
namespace App\Evaluators\Suite;
use App\Agents\ObservedWeatherAgent;
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\Evaluators\TeachingCases;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Evaluation\Assertions\StringContains;
use NeuronAI\Evaluation\Assertions\Trajectory\ToolWasCalled;
use NeuronAI\Evaluation\BaseEvaluator;
use NeuronAI\Evaluation\Contracts\DatasetInterface;
use NeuronAI\Evaluation\Conversation\Trajectory;
use NeuronAI\Evaluation\Dataset\ArrayDataset;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\UniqueIdGenerator as Id;
final class WeatherEvaluator extends BaseEvaluator
{
    private readonly AIProviderInterface $provider;
    public function __construct(?AIProviderInterface $provider = null)
    {
        parent::__construct();
        $this->provider = $provider
            ?? new ProviderFactory(Settings::fromEnv($_ENV))->create();
    }
    #[\Override]
    public function getDataset(): DatasetInterface
    {
        return new ArrayDataset(TeachingCases::weather());
    }
    #[\Override]
    public function run(array $item): mixed
    {
        $agent = ObservedWeatherAgent::make($this->provider)
            ->setThreadId(Id::generateId('eval_'));
        $agent->chat(new UserMessage(
            "What is the fixture temperature in {$item['city']}?"
        ));
        return Trajectory::fromChatHistory($agent->getChatHistory());
    }
    #[\Override]
    public function evaluate(mixed $output, array $item): void
    {
        $this->assert(new ToolWasCalled('fixture_temperature',
            ['city' => $item['city']]), $output, 'tool trajectory');
        $this->assert(new StringContains($item['reference']),
            $output->finalAnswer(), 'reported value');
    }
}
