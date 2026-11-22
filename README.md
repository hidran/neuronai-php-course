# Component facades: providers, embeddings and vector stores from configuration

Source code of the lesson "Component facades: providers, embeddings and vector stores from configuration" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Copy, not mutate: the agent's concurrency story": compare it with the parent commit to see the lesson's work.

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

- `app/Console/Commands/Components.php`
- `app/Neuron/Agents/SupportDocsAgent.php`
- `app/Providers/AppServiceProvider.php`
- `exercises/13-07-component-facades.md`

## Exercise

See `exercises/13-07-component-facades.md`.
