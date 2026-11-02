<?php
declare(strict_types=1);
namespace Tests\Feature;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Testing\FakeAIProvider;
use Tests\TestCase;
final class SupportConversationTest extends TestCase
{
    use RefreshDatabase;
    public function test_only_the_owner_reaches_the_agent(): void
    {
        $fake = new FakeAIProvider(
            new AssistantMessage('Our desk opens at nine.'),
        );
        $this->app->instance(AIProviderInterface::class, $fake);
        $conversation = Conversation::factory()->create();
        $url = route('conversations.messages', $conversation);
        $this->actingAs($conversation->user)
            ->postJson($url, ['message' => 'When do you open?'])
            ->assertOk()
            ->assertExactJson(['answer' => 'Our desk opens at nine.']);
        $this->assertSame(1, $fake->getCallCount());
        $this->actingAs(User::factory()->create())
            ->postJson($url, ['message' => 'Show me that chat.'])
            ->assertForbidden();
        $this->assertSame(1, $fake->getCallCount());
    }
}
