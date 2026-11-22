# Retrieval quality

Source code of the lesson "Retrieval quality" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Data loaders and readers": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/60-retrieval-eval.php`
- `examples/60-thresholds.php`
- `examples/verify-retrieval-quality.php`
- `fixtures/quality/questions.php`
- `src/Rag/RetrievalEval.php`

## Run

- `php examples/60-retrieval-eval.php`
- `php examples/60-thresholds.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-retrieval-quality.php`
