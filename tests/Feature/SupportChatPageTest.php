<?php
declare(strict_types=1);
namespace Tests\Feature;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Testing\FakeAIProvider;
use Tests\TestCase;
final class SupportChatPageTest extends TestCase
{
    use RefreshDatabase;
    public function test_guests_are_sent_to_the_login(): void
    {
        $conversation = Conversation::factory()->create();
        $this->get(route('conversations.index'))
            ->assertRedirect(route('login'));
        $this->get(route('conversations.show', $conversation))
            ->assertRedirect(route('login'));
        $url = route('conversations.messages.web', $conversation);
        $this->post($url, ['message' => 'Hi'])
            ->assertRedirect(route('login'));
        $this->postJson($url, ['message' => 'Hi'])
            ->assertUnauthorized();
    }
    public function test_the_page_shows_stored_lines_in_order(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = $conversation->threadId();
        $store = $this->app->make(MessageStoreInterface::class);
        $store->append($thread, new UserMessage('When do you open?'));
        $store->append($thread, new AssistantMessage('At nine.'));
        $this->actingAs($conversation->user)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSeeInOrder(['When do you open?', 'At nine.']);
    }
    public function test_another_user_never_reaches_the_agent(): void
    {
        $fake = $this->fake(new AssistantMessage('unused'));
        $conversation = Conversation::factory()->create();
        $url = route('conversations.messages.web', $conversation);
        $this->actingAs(User::factory()->create());
        $this->get(route('conversations.show', $conversation))
            ->assertForbidden();
        $this->postJson($url, ['message' => 'Hi'])->assertForbidden();
        $this->assertSame(0, $fake->getCallCount());
    }
    public function test_a_second_post_carries_the_history(): void
    {
        $fake = $this->fake(
            new AssistantMessage('Noted.'),
            new AssistantMessage('It is 4417.'),
        );
        $conversation = Conversation::factory()->create();
        $url = route('conversations.messages.web', $conversation);
        $this->actingAs($conversation->user)
            ->postJson($url, ['message' => 'My order number is 4417.'])
            ->assertOk()
            ->assertExactJson(['answer' => 'Noted.']);
        $stored = ChatMessage::query()
            ->where('thread_id', $conversation->threadId());
        $this->assertSame(2, $stored->count());
        $this->postJson($url, ['message' => 'What was it?'])
            ->assertOk();
        $sent = array_map(
            fn (Message $message) => $message->getContent(),
            $fake->getRecorded()[1]->messages,
        );
        $this->assertSame(
            ['My order number is 4417.', 'Noted.', 'What was it?'],
            $sent,
        );
    }
    public function test_an_empty_message_never_calls_the_model(): void
    {
        $fake = $this->fake(new AssistantMessage('unused'));
        $conversation = Conversation::factory()->create();
        $url = route('conversations.messages.web', $conversation);
        $this->actingAs($conversation->user)
            ->postJson($url, ['message' => ''])
            ->assertUnprocessable();
        $this->assertSame(0, $fake->getCallCount());
    }
    private function fake(AssistantMessage ...$answers): FakeAIProvider
    {
        $fake = new FakeAIProvider(...$answers);
        $this->app->instance(AIProviderInterface::class, $fake);
        return $fake;
    }
}
