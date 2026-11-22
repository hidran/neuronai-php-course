# See the endpoint's traces in Inspector: one request, one timeline

Source code of the lesson "See the endpoint's traces in Inspector: one request, one timeline" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Lab: five minutes to the first response (POST /api/ask)": compare it with the parent commit to see the lesson's work.

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

- `.env.example`
- `app/Providers/AppServiceProvider.php`
- `bootstrap/app.php`
- `composer.json`
- `exercises/13-04b-endpoint-traces-in-inspector.md`

## Exercise

See `exercises/13-04b-endpoint-traces-in-inspector.md`.
