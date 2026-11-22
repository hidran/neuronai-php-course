# EloquentMessageStore: the migration and model we write ourselves

Source code of the lesson "EloquentMessageStore: the migration and model we write ourselves" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Agents in the container": compare it with the parent commit to see the lesson's work.

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

- `app/Neuron/Agents/SupportAgent.php`
- `config/support.php`
- `exercises/13-10-eloquent-message-store.md`
- `tests/Feature/ChatMessageStoreTest.php`

## Exercise

See `exercises/13-10-eloquent-message-store.md`.
