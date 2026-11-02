<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Neuron\Agents\SupportAgent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use Tests\TestCase;
final class SupportAgentTest extends TestCase
{
    public function test_for_binds_a_copy(): void
    {
        $reply = new AssistantMessage('We open at 9.');
        $fake = new FakeAIProvider($reply);
        $agent = new SupportAgent($fake);
        $copy = $agent->for('ticket-42');
        $state = $copy->chat(new UserMessage('When do you open?'));
        $this->assertNull($agent->getThreadId());
        $this->assertSame('ticket-42', $copy->getThreadId());
        $answer = $state->getMessage()?->getContent();
        $this->assertSame('We open at 9.', $answer);
        $this->assertSame(1, $fake->getCallCount());
    }
}
