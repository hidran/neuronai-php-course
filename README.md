# Source types and file identifiers

Source code of the lesson "Source types and file identifiers" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Images and documents as content blocks": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/43-source-types.php`
- `examples/configure-http-client.php`
- `examples/exercise-source-references.php`
- `examples/verify-sources.php`
- `exercises/08-02-source-types-file-id.md`
- `fixtures/sources/exercise-invoice.pdf`
- `fixtures/sources/invoice.pdf`
- `requests/upload-exercise-invoice.http`
- `requests/upload-invoice.http`
- `src/Agents/SourceAgent.php`
- `src/Http/FilesApiClient.php`
- `src/SourceExamples.php`

## Run

- `php examples/43-source-types.php`
- `php examples/exercise-source-references.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-sources.php`

## Exercise

See `exercises/08-02-source-types-file-id.md`.
