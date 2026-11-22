# Data loaders and readers

Source code of the lesson "Data loaders and readers" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Our first RAG agent, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `composer.json`
- `examples/55-data-loaders.php`
- `examples/exercise-loaders.php`
- `examples/verify-loaders.php`
- `exercises/11-05-data-loaders-and-readers.md`
- `fixtures/docs/billing.md`
- `fixtures/docs/setup.md`
- `fixtures/docs/support.md`
- `fixtures/rag-docs/return-policy.md`
- `fixtures/rag-docs/support-page.html`
- `fixtures/rag-docs/troubleshooting.txt`
- `fixtures/rag-docs/warranty.pdf`
- `src/Rag/MainContentReader.php`

## Run

- `php examples/55-data-loaders.php`
- `php examples/exercise-loaders.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-loaders.php`

## Exercise

See `exercises/11-05-data-loaders-and-readers.md`.
