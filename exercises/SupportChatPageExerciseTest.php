<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Testing\FakeAIProvider;
use Tests\TestCase;

// Solution of the exercise, as a class of its own so it runs beside the
// lesson's SupportChatPageTest. Copy it to tests/Feature/ to try it.
final class SupportChatPageExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_empty_answer_becomes_a_502(): void
    {
        $fake = $this->fake(new AssistantMessage(''));
        $conversation = Conversation::factory()->create();
        $url = route('conversations.messages.web', $conversation);
        $this->actingAs($conversation->user)
            ->postJson($url, ['message' => 'Where is my invoice?'])
            ->assertStatus(502)
            ->assertExactJson(['error' => 'The agent returned no answer.']);
        $this->assertSame(1, $fake->getCallCount());
        $stored = ChatMessage::query()
            ->where('thread_id', $conversation->threadId());
        $this->assertSame(2, $stored->count());
        $this->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Where is my invoice?');
    }

    public function test_a_too_long_message_never_calls_the_model(): void
    {
        $fake = $this->fake(new AssistantMessage('unused'));
        $conversation = Conversation::factory()->create();
        $url = route('conversations.messages.web', $conversation);
        $this->actingAs($conversation->user)
            ->postJson($url, ['message' => str_repeat('a', 2001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
        $this->assertSame(0, $fake->getCallCount());
        $this->postJson($url, ['message' => str_repeat('a', 2000)])
            ->assertOk();
        $this->assertSame(1, $fake->getCallCount());
    }

    private function fake(AssistantMessage ...$answers): FakeAIProvider
    {
        $fake = new FakeAIProvider(...$answers);
        $this->app->instance(AIProviderInterface::class, $fake);

        return $fake;
    }
}
