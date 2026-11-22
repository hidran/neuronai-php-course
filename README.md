# Images and documents as content blocks

Source code of the lesson "Images and documents as content blocks" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Streaming with tools, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/42-media-content.php`
- `examples/exercise-media-content.php`
- `exercises/08-01-media-content-blocks.md`
- `fixtures/media/expected.json`
- `fixtures/media/note.pdf`
- `fixtures/media/note.txt`
- `fixtures/media/shapes.png`
- `src/Agents/MediaAgent.php`
- `src/MediaFixtures.php`

## Run

- `php examples/42-media-content.php`
- `php examples/exercise-media-content.php`

## Exercise

See `exercises/08-01-media-content-blocks.md`.
