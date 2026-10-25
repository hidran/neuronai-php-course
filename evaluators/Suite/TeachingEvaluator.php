<?php
declare(strict_types=1);
namespace App\Evaluators\Suite;
use App\Agents\TeachingAgent;
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\Evaluators\TeachingCases;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Evaluation\Assertions\StringContains as Contains;
use NeuronAI\Evaluation\Assertions\StringLengthBetween as Length;
use NeuronAI\Evaluation\BaseEvaluator;
use NeuronAI\Evaluation\Contracts\DatasetInterface;
use NeuronAI\Evaluation\Conversation\Trajectory;
use NeuronAI\Evaluation\Dataset\ArrayDataset;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\UniqueIdGenerator as Id;
final class TeachingEvaluator extends BaseEvaluator
{
    private readonly AIProviderInterface $provider;
    /** @param list<array<string, string|int>>|null $cases */
    public function __construct(
        ?AIProviderInterface $provider = null,
        private readonly ?array $cases = null,
        private readonly ?SystemMessage $prompt = null,
    ) {
        parent::__construct();
        // The neuron CLI builds evaluators without arguments.
        $this->provider = $provider
            ?? new ProviderFactory(Settings::fromEnv($_ENV))->create();
    }
    #[\Override]
    public function getDataset(): DatasetInterface
    {
        return new ArrayDataset($this->cases ?? TeachingCases::basic());
    }
    #[\Override]
    public function run(array $item): mixed
    {
        $agent = TeachingAgent::make($this->provider, $this->prompt)
            ->setThreadId(Id::generateId('eval_'));
        $agent->chat(new UserMessage($item['input']));
        return Trajectory::fromChatHistory($agent->getChatHistory());
    }
    #[\Override]
    public function evaluate(mixed $output, array $item): void
    {
        $answer = $output->finalAnswer();
        $this->assert(new Contains($item['reference']), $answer,
            'reference concept');
        $this->assert(new Length($item['min'], $item['max']), $answer,
            'character budget');
    }
}
