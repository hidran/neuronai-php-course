# Artisan generators

Source code of the lesson "Artisan generators" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Configuration and environments": compare it with the parent commit to see the lesson's work.

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

- `app/Neuron/Agents/AssistantAgent.php`
- `app/Neuron/Agents/Tools/OfficeHours.php`
- `app/Neuron/Nodes/NormalizeQuestion.php`
- `exercises/13-03-artisan-generators.md`

## Exercise

See `exercises/13-03-artisan-generators.md`.
