# The Neuron facade, and what to use instead

Source code of the lesson "The Neuron facade, and what to use instead" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "See the endpoint's traces in Inspector: one request, one timeline": compare it with the parent commit to see the lesson's work.

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

- `app/Console/Commands/Diagnose.php`
- `app/Neuron/Middleware/AuditMiddleware.php`
- `app/Neuron/Output/SupportHours.php`
- `app/Neuron/Tools/OfficeHours.php`
- `exercises/13-05-neuron-facade-alternative.md`
- `app/Neuron/Agents/Tools/OfficeHours.php` (removed)

## Exercise

See `exercises/13-05-neuron-facade-alternative.md`.
