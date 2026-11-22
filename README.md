# A read-only database analyst

Source code of the lesson "A read-only database analyst" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Lab: weather and calculator agent, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `database/compose.analyst.yaml`
- `database/exercise-answer.sql`
- `database/inspect.sql`
- `database/seed.sql`
- `examples/31-database-analyst.php`
- `examples/runtime-check-analyst.php`
- `src/Agents/DataAnalystAgent.php`

## Run

- `php examples/31-database-analyst.php`
