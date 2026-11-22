# Embeddings and similarity

Source code of the lesson "Embeddings and similarity" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "A deterministic test suite with fake providers and tools, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/54-embeddings-similarity.php`
- `examples/54-vector-contracts.php`
- `examples/exercise-embeddings.php`
- `examples/verify-embeddings-config.php`
- `src/Config/EmbeddingsFactory.php`
- `src/Config/EmbeddingsProvider.php`
- `src/Config/EmbeddingsSettings.php`

## Run

- `php examples/54-embeddings-similarity.php`
- `php examples/54-vector-contracts.php`
- `php examples/exercise-embeddings.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-embeddings-config.php`
