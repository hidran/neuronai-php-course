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
}
