# Laravel Boost and AI-assisted development: reviewing the proposal

Source code of the lesson "Laravel Boost and AI-assisted development: reviewing the proposal" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Component facades: providers, embeddings and vector stores from configuration": compare it with the parent commit to see the lesson's work.

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
- `checks/verify-boost-skills.php`
- `composer.json`
- `exercises/13-08-laravel-boost-skills.md`
- `proposals/RefundTool.php`
- `requests/support-agent.txt`
- `tests/Unit/SupportAgentTest.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php checks/verify-boost-skills.php`

## Exercise

See `exercises/13-08-laravel-boost-skills.md`.
