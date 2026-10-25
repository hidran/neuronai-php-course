<?php
declare(strict_types=1);
use App\Deterministic\StubWeatherClient;
use App\Deterministic\WeatherTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ProviderException;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
use PHPUnit\Framework\TestCase;
final class OrchestrationTest extends TestCase
{
    private function request(): ToolCallMessage
    {
        return new ToolCallMessage(null, [
            new ToolCall('get_current_weather', 'weather_1', [
                'latitude' => 45.07, 'longitude' => 7.69,
            ]),
        ]);
    }
    public function testToolResultReachesSecondInference(): void
    {
        $fake = new FakeAIProvider(
            $this->request(),
            new AssistantMessage('It is 14 degrees in Turin.'),
        );
        $calls = new ArrayObject();
        $state = Agent::make()->setThreadId('orchestration')
            ->setAiProvider($fake)
            ->addTool(new WeatherTool(new StubWeatherClient($calls)))
            ->chat(new UserMessage('Weather in Turin?'));
        self::assertSame('It is 14 degrees in Turin.',
            $state->getMessage()->getContent());
        $fake->assertCallCount(2);
        $fake->assertToolsConfigured(['get_current_weather']);
        self::assertSame([[45.07, 7.69]], $calls->getArrayCopy());
        $second = $fake->getRecorded()[1];
        $results = array_values(array_filter($second->messages,
            static fn ($m): bool => $m instanceof ToolResultMessage));
        self::assertCount(1, $results);
        $settled = $results[0]->getToolCalls();
        self::assertCount(1, $settled);
        self::assertSame('weather_1', $settled[0]->getCallId());
        self::assertSame('14 Celsius', $settled[0]->getResult());
    }
    public function testExhaustedQueueRejectsExtraInference(): void
    {
        $fake = new FakeAIProvider($this->request());
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('response queue is empty');
        Agent::make()->setThreadId('exhaustion')
            ->setAiProvider($fake)
            ->addTool(new WeatherTool(
                new StubWeatherClient(new ArrayObject())))
            ->chat(new UserMessage('Weather?'));
    }
}
