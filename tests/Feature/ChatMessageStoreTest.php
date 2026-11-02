<?php
declare(strict_types=1);
namespace Tests\Feature;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Neuron\Agents\SupportAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NeuronAI\Chat\History\EloquentMessageStore;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Testing\FakeAIProvider;
use Tests\TestCase;
final class ChatMessageStoreTest extends TestCase
{
    use RefreshDatabase;
    public function test_the_same_message_is_stored_once(): void
    {
        $store = new EloquentMessageStore(ChatMessage::class);
        $message = new UserMessage('Where is my invoice?');
        $store->append('demo-thread-a', $message);
        $store->append('demo-thread-a', $message);
        $loaded = $store->loadAll('demo-thread-a');
        $this->assertCount(1, $loaded);
        $this->assertSame($message->getId(), $loaded[0]->getId());
    }
    public function test_threads_stay_apart_and_archive_forgets(): void
    {
        $store = new EloquentMessageStore(ChatMessage::class);
        $mine = new UserMessage('My question');
        $theirs = new UserMessage('Another customer');
        $store->append('demo-thread-a', $mine);
        $store->append('demo-thread-b', $theirs);
        $a = $this->ids($store->loadAll('demo-thread-a'));
        $b = $this->ids($store->loadAll('demo-thread-b'));
        $this->assertSame([$mine->getId()], $a);
        $this->assertSame([$theirs->getId()], $b);
        $store->archive('demo-thread-a', 1);
        $this->assertSame([], $store->loadActive('demo-thread-a'));
        $this->assertCount(1, $store->loadAll('demo-thread-a'));
    }
    public function test_a_fresh_agent_sends_the_stored_question(): void
    {
        $thread = Conversation::factory()->create()->threadId();
        $first = new FakeAIProvider(new AssistantMessage('Noted.'));
        $this->app->instance(AIProviderInterface::class, $first);
        $this->app->make(SupportAgent::class)
            ->for($thread)
            ->chat(new UserMessage('My order number is 4417.'));
        $second = new FakeAIProvider(new AssistantMessage('It is 4417.'));
        $this->app->instance(AIProviderInterface::class, $second);
        $this->app->make(SupportAgent::class)
            ->for($thread)
            ->chat(new UserMessage('What was my order number?'));
        $sent = $this->texts($second->getRecorded()[0]->messages);
        $this->assertContains('My order number is 4417.', $sent);
    }
    /** @return list<string> */
    private function ids(array $messages): array
    {
        return array_map(fn (Message $m) => $m->getId(), $messages);
    }
    /** @return list<?string> */
    private function texts(array $messages): array
    {
        return array_map(fn (Message $m) => $m->getContent(), $messages);
    }
}
