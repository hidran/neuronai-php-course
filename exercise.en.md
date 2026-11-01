# Exercise: a required application column on chat_messages

Start from the Laravel project at the end of the lesson: our own `create_chat_messages_table` migration, the `ChatMessage` model with five fillable fields, `NeuronServiceProvider` binding `EloquentMessageStore(ChatMessage::class)`, and `ChatMessageStoreTest`.

1. Create a new migration that adds a required `conversation_id` column to `chat_messages`: a foreign id to `conversations`, not nullable, with an index. Run `php artisan migrate`.
2. Run the "Laravel tests" run configuration. Find the tests that fail and explain why: the store calls `firstOrCreate` with `thread_id`, `message_id`, `role`, `content` and `meta` only, so the new required column has no value.
3. Fix it in the model, not in the store: in `ChatMessage`, register a `creating` callback (for example in `booted()`) that fills `conversation_id` from the thread identifier. Our threads look like `user-<user id>:conversation-<conversation id>`; parse the conversation id from that string, and throw an exception when the thread does not belong to a conversation. Add a `conversation()` relationship.
4. The two store tests that use the free demonstration threads (`demo-thread-a`, `demo-thread-b`) now fail on purpose. Change them to use the `threadId()` of conversations created with the factory.

Acceptance: all tests pass again in the "Laravel tests" run configuration; a row stored through a `SupportAgent` turn has the right `conversation_id`; `EloquentMessageStore` and `NeuronServiceProvider` are unchanged; appending to a thread string that names no conversation fails instead of storing an orphan row.

Without PhpStorm, `php artisan test` runs the same suite. The offline check of the lesson, `php verify-eloquent-message-store.php <your project>`, ends with `ELOQUENT MESSAGE STORE OK` on the project of the lesson, before this exercise.
