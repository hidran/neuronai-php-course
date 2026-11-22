# Exercise: read what the SDK installed

Start from the Laravel application at the end of the lesson: Laravel 13, `"php": ">=8.5"` in `composer.json`, `neuron-core/neuron-laravel` on `^2.0` and `neuron-core/neuron-ai` on `^4.1`.

1. Run `composer show neuron-core/neuron-ai` and `composer show neuron-core/neuron-laravel`. Write down the exact versions and find the same versions in `composer.lock`. Explain in one sentence why the lock file belongs in version control.
2. Run `composer why neuron-core/neuron-ai`. It lists two reasons: our own `composer.json` and the SDK. Remove nothing; explain which of the two is the application's decision.
3. Open `vendor/neuron-core/neuron-laravel/src/Commands/stubs/node.stub` and `vendor/neuron-core/neuron-ai/src/Workflow/Events/`. Write the two corrected `use` lines the stub should have.
4. Open `vendor/neuron-core/neuron-ai/src/Chat/History/EloquentMessageStore.php` and find the columns it writes. Compare them with the SDK's `create_chat_messages_table` migration and name the missing column.
5. Start `php artisan serve` and send `GET /up` from a `.http` file in PhpStorm. Then stop the server and send it again: what does the HTTP client show now?

Acceptance: two versions recorded and found in the lock file; one sentence on direct versus transitive requirements; `use NeuronAI\Workflow\Events\StartEvent;` and `use NeuronAI\Workflow\Events\StopEvent;`; the missing column is `message_id`; a 200 with the server running and a connection error without it. No model call is needed.
