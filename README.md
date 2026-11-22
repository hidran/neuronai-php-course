# Our first RAG agent

Source code of the lesson "Our first RAG agent" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Vector stores and retrieval, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/53-first-rag.php`
- `examples/exercise-first-rag.php`
- `examples/rag-bootstrap.php`
- `examples/verify-first-rag.php`
- `fixtures/rag/cedar-policy.txt`
- `fixtures/rag/dispatch-policy.txt`
- `src/Config/RagServices.php`
- `src/Rag/ReturnsPolicyAgent.php`

## Run

- `php examples/53-first-rag.php`
- `php examples/exercise-first-rag.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-first-rag.php`
