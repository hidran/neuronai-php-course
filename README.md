# Hello, NeuronAI: our first agent answers

Source code of the lesson "Hello, NeuronAI: our first agent answers" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Keys and environment: .env, .env.example, what never gets committed": compare it with the parent commit to see the lesson's work.

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
- `examples/01-hello.php`
- `examples/verify-hello.php`
- `src/Config/ProviderFactory.php`
- `src/HelloAgent.php`

## Run

- `php examples/01-hello.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-hello.php`
