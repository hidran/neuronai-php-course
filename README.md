# Documents and chunking

Source code of the lesson "Documents and chunking" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Embeddings and similarity, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/56-chunking.php`
- `examples/56-compare.php`
- `examples/exercise-chunking.php`
- `exercises/11-02-documents-and-chunking.md`
- `fixtures/rag-chunks/faq.md`
- `fixtures/rag-chunks/returns-guide.md`
- `src/Rag/ChunkComparison.php`
- `src/Rag/HeadingSplitter.php`

## Run

- `php examples/56-chunking.php`
- `php examples/56-compare.php`
- `php examples/exercise-chunking.php`

## Exercise

See `exercises/11-02-documents-and-chunking.md`.
