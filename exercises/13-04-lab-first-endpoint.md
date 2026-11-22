# Exercise: cover the whole contract with tests

Start from the Laravel project at the end of the lab: `POST /api/ask`, `AskController`, `AssistantAgent` (provider injected through its constructor, support instructions as a `SystemMessage`), the `AIProviderInterface` binding in `AppServiceProvider`, `requests/ask.http` and `tests/Feature/AskEndpointTest.php`.

1. Add a test for the deliberate error: bind `new AssistantAgent($fake)` where the fake provider answers with an empty `AssistantMessage('')`, post a valid message and assert status 502 and the exact JSON `{"error": "The agent returned no answer."}`.
2. Add a test for the size limit: a message of 2001 characters returns 422 and the fake records no call; a message of exactly 2000 characters returns 200.
3. Add a test that two requests in a row never share a conversation: give the fake two responses, send two requests, and assert that the second recorded request (`$fake->getRecorded()[1]->messages`) contains only one `UserMessage`.
4. Add a fifth request to `requests/ask.http` whose body is `{"message": ""}`, send it from PhpStorm and explain which validation rule rejects it.
5. Instead of binding the agent, bind the fake to the interface in one test: `$this->app->instance(AIProviderInterface::class, $fake)`. Why does the container now build `AssistantAgent` with the fake?

Acceptance: `php artisan test --filter=AskEndpointTest` passes with six tests, no test reaches a real provider, the empty string is rejected by `required` with a 422, and the interface binding replaces the provider for every class that asks for it.
