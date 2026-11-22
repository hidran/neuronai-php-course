# Exercise: two more promises of the message route

Start from the Laravel project at the end of the lesson: `tests/Feature/SupportChatPageTest.php` with its five
tests and the `fake()` helper, and a suite of eleven green tests.

The message route makes two more promises that no test checks yet. When the agent comes back with no text, the
controller answers **502** with its own message, `The agent returned no answer.`, and the page shows that message
under the form and clears the box. And a message longer than **2000** characters is refused before any model call. **Write one feature
test for each promise, without calling a model, then prove that each test can fail.**

Write them in a new class, `tests/Feature/SupportChatPageExerciseTest.php`, with `RefreshDatabase` and a copy
of the lesson's `fake()` helper, so the lesson's file stays as it is.

## Steps

1. **The empty answer.** Queue one `AssistantMessage` with an empty text in the fake. Post a question as the
   owner of a new conversation and assert the status with `assertStatus(502)` and the exact JSON
   `{"error": "The agent returned no answer."}`.
2. **Ask the fake.** Was the model called? How many times? Write the check with `getCallCount()`, as in the
   lesson, never with the fake's own `assert*` methods.
3. **Find out what was stored, before you assert it.** The page clears the box as if the question went
   through. Is it really in the table? Count the `ChatMessage` rows of the conversation's thread and *write the
   number you observe*, then open the conversation page in the same test and check what the person sees after a
   reload.
4. **The length limit.** In a second test, post a message of 2001 characters (`str_repeat('a', 2001)`): assert
   422, an error on the `message` field (`assertJsonValidationErrors`) and zero provider calls.
5. **The boundary.** In the same test, post exactly 2000 characters: it must pass, and the fake must now have
   received *one* call. A limit is only tested when both sides of it are.
6. **Make each test fail once.** In `ConversationMessageController`, change `502` to `200` and run your class;
   then put it back, change `max:2000` to `max:5000`, run again, and put it back. Each change must turn exactly
   one of your two tests red.

Laravel documents the assertions under *HTTP Tests*: `https://laravel.com/docs/13.x/http-tests`

## Acceptance

- `php artisan test --filter=SupportChatPageExerciseTest` shows **2 passed**, and the whole suite, run with
  PhpStorm's "Laravel tests" configuration, shows **13 tests passed**.
- With `502` changed to `200`, only the empty-answer test fails; with `max:5000`, only the length test fails.
  After you restore the controller, everything is green again.
- No test reads the demo users, calls `DemoSeeder` or needs a key: unset the provider key in a copy of the
  project and the suite still passes.
- `SupportChatPageTest.php` and `ConversationMessageController.php` are unchanged at the end.

`php artisan test` runs the whole suite offline, so it tells you that the project is sound; for your own test file,
use the list above.

## Worked solution

```php
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
```

**Step 2.** One call: a 502 is not a refusal before the model, like the 403 and the 422 of the lesson. The agent
ran, and the provider answered with nothing.

**Step 3.** Two rows. By the time the controller sees the empty text, the agent has already stored the question
and the empty answer through the message store. That confirms what the page assumes when it clears the box on a
502: the question went through, only the answer is missing, and there is nothing to send again. After a reload the
question is in the transcript (the empty answer has no text, so the transcript leaves it out). After every other error
(a refusal, an expired page, a provider failure) the table holds nothing new, which is why the page marks only those
questions "not sent".
The step asks you to observe before you assert because the number is a fact of the application, not of the page.

**Step 5.** The second post needs no second answer in the fake's queue: the fake was built with one answer, the
first post never reached it, so the 2000-character post receives `unused`. The count going from zero to one is the
proof that the boundary message, and only that one, reached the model.

**Step 6.** `502` changed to `200` turns the first test red on `assertStatus(502)`. `max:5000` turns the second
red on `assertUnprocessable()`: the 2001-character message now passes validation and reaches the agent. Neither
change touches the other test, which is what "one test per promise" buys.
