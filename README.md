# Vector stores and retrieval

Source code of the lesson "Vector stores and retrieval" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Documents and chunking": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/57-file-query.php`
- `examples/57-vector-stores.php`
- `examples/exercise-vector-stores.php`
- `examples/verify-store-config.php`
- `examples/verify-vector-stores.php`
- `src/Config/VectorStoreFactory.php`
- `src/Config/VectorStoreSettings.php`
- `src/Rag/KeywordEmbeddings.php`

## Run

- `php examples/57-file-query.php`
- `php examples/57-vector-stores.php`
- `php examples/exercise-vector-stores.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-store-config.php`
- `php examples/verify-vector-stores.php`
