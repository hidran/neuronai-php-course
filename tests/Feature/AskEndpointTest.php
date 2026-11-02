<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Neuron\Agents\AssistantAgent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Testing\FakeAIProvider;
use Tests\TestCase;

final class AskEndpointTest extends TestCase
{
    public function test_the_answer_comes_from_the_agent(): void
    {
        $fake = new FakeAIProvider(
            new AssistantMessage('Monday to Friday, 9:00 to 17:00.'),
        );
        $this->app->instance(
            AssistantAgent::class,
            new AssistantAgent($fake),
        );

        $this->postJson('/api/ask', ['message' => 'When are you open?'])
            ->assertOk()
            ->assertExactJson(['answer' => 'Monday to Friday, 9:00 to 17:00.']);

        $this->assertSame(1, $fake->getCallCount());
    }

    public function test_invalid_input_never_reaches_the_model(): void
    {
        $fake = new FakeAIProvider();
        $this->app->instance(
            AssistantAgent::class,
            new AssistantAgent($fake),
        );

        $this->postJson('/api/ask', [])->assertUnprocessable();
        $this->postJson('/api/ask', ['message' => 42])->assertUnprocessable();

        $this->assertSame(0, $fake->getCallCount());
    }
}
