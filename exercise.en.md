# Exercise: a queue job that uses the same construction path

Start from the Laravel project at the end of the lesson: `SupportAgent` receives `AIProviderInterface`, `MessageStoreInterface` and `PersistenceInterface` through its constructor, `NeuronServiceProvider` binds a shared `EloquentMessageStore` and a fresh `DatabasePersistence`, and `ConversationMessageController` authorizes the conversation before calling `for()`.

1. Create a job with `php artisan make:job AnswerConversation`. Give it the conversation id and the message text as constructor arguments, and let `handle()` receive `SupportAgent $agent` by method injection.
2. In `handle()`, load the conversation, derive the thread with `$conversation->threadId()`, and run `$agent->for(...)->chat(new UserMessage(...))`. The job has no HTTP request and no authenticated user: the code that dispatches it is where the application decides the conversation may be used. Write that decision down in a comment above `dispatch()`.
3. Add a feature test: bind a `FakeAIProvider` as `AIProviderInterface` with `$this->app->instance()`, dispatch the job synchronously, and assert that the provider received one call and that `chat_messages` holds two rows for the conversation's thread.
4. Run `php artisan agent:lifetimes` again. Explain in one sentence why the job would be wrong if `SupportAgent` were registered as a singleton in a long running queue worker.

Acceptance: the job contains no `new SupportAgent`, no provider construction and no `auth()` call; the new test passes together with `SupportConversationTest` in the "Laravel tests" run configuration; the diagnostic still prints `EloquentMessageStore: shared`, `DatabasePersistence: fresh`, `SupportAgent: fresh`.

Without PhpStorm, `php artisan test` runs the same suite. The offline check of the lesson, `php verify-agents-in-container.php <your project>`, ends with `AGENTS IN CONTAINER OK`.
