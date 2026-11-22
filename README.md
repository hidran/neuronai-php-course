# Lab: documentation RAG

Source code of the lesson "Lab: documentation RAG" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Retrieval quality, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/58-docs-ask.php`
- `examples/58-docs-chunks.php`
- `examples/58-docs-index.php`
- `examples/58-docs-retrieve.php`
- `examples/exercise-docs-sync.php`
- `examples/verify-docs-rag.php`
- `fixtures/docs-questions.php`
- `src/Docs/DocsAgent.php`
- `src/Docs/DocsSync.php`
- `src/Docs/MarkdownSectionSplitter.php`

## Run

- `php examples/58-docs-ask.php`
- `php examples/58-docs-chunks.php`
- `php examples/58-docs-index.php`
- `php examples/58-docs-retrieve.php`
- `php examples/exercise-docs-sync.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-docs-rag.php`
