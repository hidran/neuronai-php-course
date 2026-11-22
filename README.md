# Agents in the container

Source code of the lesson "Agents in the container" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Laravel Boost and AI-assisted development: reviewing the proposal": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Put your own provider key in `.env` (never commit it). The tests run without any key: `php artisan test`.

## What this lesson changes

- `app/Http/Controllers/ConversationMessageController.php`
- `app/Models/ChatMessage.php`
- `app/Models/Conversation.php`
- `app/Neuron/Agents/SupportAgent.php`
- `app/Policies/ConversationPolicy.php`
- `app/Providers/NeuronServiceProvider.php`
- `bootstrap/providers.php`
- `checks/verify-agents-in-container.php`
- `database/factories/ConversationFactory.php`
- `database/migrations/2026_11_01_000100_create_conversations_table.php`
- `database/migrations/2026_11_01_000200_create_chat_messages_table.php`
- `exercises/13-09-agents-in-container.md`
- `routes/api.php`
- `routes/console.php`
- `tests/Feature/SupportConversationTest.php`
- `tests/Unit/SupportAgentTest.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php checks/verify-agents-in-container.php`

## Exercise

See `exercises/13-09-agents-in-container.md`.
