# Copy, not mutate: the agent's concurrency story

Source code of the lesson "Copy, not mutate: the agent's concurrency story" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "The Neuron facade, and what to use instead": compare it with the parent commit to see the lesson's work.

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

- `checks/verify-copy-not-mutate.php`
- `diagnostics/copies.php`
- `exercises/13-06-copy-not-mutate.md`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php checks/verify-copy-not-mutate.php`

## Exercise

See `exercises/13-06-copy-not-mutate.md`.
