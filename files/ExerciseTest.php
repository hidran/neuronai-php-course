<?php
declare(strict_types=1);
use App\Deterministic\StubWeatherClient;
use App\Deterministic\WeatherTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolCall;
use NeuronAI\Tools\ToolProperty;
use PHPUnit\Framework\TestCase;
final class AddAmountsTool extends Tool
{
    protected string $name = 'add_amounts';
    protected ?string $description = 'Adds two amounts.';
    public function __construct(private readonly ArrayObject $calls)
    {
    }
    #[\Override]
    protected function properties(): array
    {
        return [
            new ToolProperty(name: 'left', type: PropertyType::NUMBER,
                description: 'First amount', required: true),
            new ToolProperty(name: 'right', type: PropertyType::NUMBER,
                description: 'Second amount', required: true),
        ];
    }
    public function __invoke(float $left, float $right): string
    {
        $this->calls[] = [$left, $right];
        return (string) ($left + $right);
    }
}
final class ExerciseTest extends TestCase
{
    public function testTwoCallsKeepTheirResultsAssociated(): void
    {
        $fake = new FakeAIProvider(new ToolCallMessage(null, [
            new ToolCall('get_current_weather', 'weather_x', [
                'latitude' => 45.07, 'longitude' => 7.69,
            ]),
            new ToolCall('add_amounts', 'sum_x', [
                'left' => 3, 'right' => 8,
            ]),
        ]), new AssistantMessage('Done.'));
        $weather = new ArrayObject();
        $calculator = new ArrayObject();
        Agent::make()->setThreadId('exercise')
            ->setAiProvider($fake)->addTool([
                new WeatherTool(new StubWeatherClient($weather)),
                new AddAmountsTool($calculator),
            ])->chat(new UserMessage('Use both tools.'));
        self::assertSame([[45.07, 7.69]], $weather->getArrayCopy());
        self::assertSame([[3.0, 8.0]], $calculator->getArrayCopy());
        $fake->assertCallCount(2);
        $messages = array_values(array_filter(
            $fake->getRecorded()[1]->messages,
            static fn ($m): bool => $m instanceof ToolResultMessage
        ));
        self::assertCount(1, $messages);
        $results = [];
        foreach ($messages[0]->getToolCalls() as $call) {
            $results[$call->getCallId()] = $call->getResult();
        }
        self::assertSame([
            'weather_x' => '14 Celsius', 'sum_x' => '11',
        ], $results);
    }
}
