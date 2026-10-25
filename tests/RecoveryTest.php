<?php
declare(strict_types=1);
use App\Deterministic\ToolErrorPolicy;
use NeuronAI\Tools\ToolCall;
use PHPUnit\Framework\TestCase;
final class RecoveryTest extends TestCase
{
    public function testRecoveryInstructionAndRedaction(): void
    {
        $policy = new ToolErrorPolicy();
        $text = $policy(new RuntimeException('secret_fixture_123'),
            new ToolCall('get_current_weather', 'failed_1'));
        self::assertStringContainsString('Retry', $text);
        self::assertStringNotContainsString('secret_fixture_123',
            $text);
    }
    public function testUnexpectedProgrammingErrorPropagates(): void
    {
        $policy = new ToolErrorPolicy();
        self::assertNull($policy(new LogicException('Bug'),
            new ToolCall('get_current_weather', 'failed_2')));
    }
}
