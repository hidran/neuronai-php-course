# Keys and environment: .env, .env.example, what never gets committed

Source code of the lesson "Keys and environment: .env, .env.example, what never gets committed" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "The project: composer init, PSR-4, NeuronAI, phpdotenv and the environment check": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `.env.example`
- `.gitignore`
- `src/Config/Provider.php`
- `src/Config/Settings.php`
- `storage/.gitkeep`
- `src/.gitkeep` (removed)
