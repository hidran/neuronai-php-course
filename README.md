# Configuration and environments

Source code of the lesson "Configuration and environments" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "A Laravel 13 project in PhpStorm, and installing the SDK": compare it with the parent commit to see the lesson's work.

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
- `config/neuron.php`
- `exercises/13-02-configuration-environments.md`
- `routes/console.php`

## Exercise

See `exercises/13-02-configuration-environments.md`.
