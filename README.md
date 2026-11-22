# Lab: five minutes to the first response (POST /api/ask)

Source code of the lesson "Lab: five minutes to the first response (POST /api/ask)" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Artisan generators": compare it with the parent commit to see the lesson's work.

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

- `app/Http/Controllers/AskController.php`
- `app/Neuron/Agents/AssistantAgent.php`
- `app/Providers/AppServiceProvider.php`
- `bootstrap/app.php`
- `composer.json`
- `config/sanctum.php`
- `database/migrations/2026_11_01_000000_create_personal_access_tokens_table.php`
- `exercises/13-04-lab-first-endpoint.md`
- `requests/ask.http`
- `routes/api.php`
- `tests/Feature/AskEndpointTest.php`

## Exercise

See `exercises/13-04-lab-first-endpoint.md`.
