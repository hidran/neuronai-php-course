# Choosing knowledge mechanisms

Source code of the lesson "Choosing knowledge mechanisms" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Lab: documentation RAG, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/59-agentic-rag.php`
- `examples/59-combined-support.php`
- `examples/59-decision-table.php`
- `examples/59-direct-context.php`
- `examples/exercise-knowledge.php`
- `examples/verify-knowledge.php`
- `fixtures/knowledge/desk-lamp.txt`
- `fixtures/knowledge/orders.sql`
- `fixtures/knowledge/refund-policy.md`
- `fixtures/knowledge/requests.json`
- `src/Knowledge/Authority.php`
- `src/Knowledge/AuthorityReport.php`
- `src/Knowledge/AuthorityRouter.php`
- `src/Knowledge/OrderDatabase.php`
- `src/Knowledge/OrderStatusTool.php`
- `src/Knowledge/SearchDocsTool.php`
- `src/Knowledge/SupportAgent.php`

## Run

- `php examples/59-agentic-rag.php`
- `php examples/59-combined-support.php`
- `php examples/59-decision-table.php`
- `php examples/59-direct-context.php`
- `php examples/exercise-knowledge.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-knowledge.php`
