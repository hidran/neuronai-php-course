# Lab: extracting orders from emails

Source code of the lesson "Lab: extracting orders from emails" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Structured output vs tool calling": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/38-order-extraction.php`
- `examples/exercise-order-extraction.php`
- `examples/verify-order.php`
- `fixtures/orders/catalog.json`
- `fixtures/orders/complete.txt`
- `fixtures/orders/incomplete.txt`
- `fixtures/orders/malformed.txt`
- `fixtures/orders/wrong-total.txt`
- `src/Agents/OrderExtractor.php`
- `src/Orders/Catalog.php`
- `src/Orders/OrderReview.php`
- `src/Output/Order.php`
- `src/Output/OrderLine.php`

## Run

- `php examples/38-order-extraction.php`
- `php examples/exercise-order-extraction.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-order.php`
