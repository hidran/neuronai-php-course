<?php
declare(strict_types=1);
use App\Deterministic\SensitiveService;
use App\Deterministic\SensitiveTool;
use App\Deterministic\StubWeatherClient;
use App\Deterministic\WeatherTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use PHPUnit\Framework\TestCase;
final class VisibilityTest extends TestCase
{
    /** @return list<string> */
    private function offered(bool $authorized): array
    {
        $fake = new FakeAIProvider(new AssistantMessage('Done.'));
        Agent::make()->setThreadId('visibility')
            ->setAiProvider($fake)->addTool([
                new WeatherTool(
                    new StubWeatherClient(new ArrayObject())),
                new SensitiveTool(new SensitiveService(), $authorized),
            ])->chat(new UserMessage('List available actions.'));
        $fake->assertCallCount(1);
        return array_map(static fn ($tool): string => $tool->getName(),
            $fake->getRecorded()[0]->tools);
    }
    public function testRestrictedCapabilityIsNotOffered(): void
    {
        self::assertSame(['get_current_weather'],
            $this->offered(false));
    }
    public function testPermittedCapabilityIsOffered(): void
    {
        self::assertSame([
            'get_current_weather', 'sensitive_teaching_tool',
        ], $this->offered(true));
    }
    public function testServiceChecksAuthorizationSeparately(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Access denied.');
        new SensitiveService()->read(false);
    }
}
