# Lab: persistent CLI chat with /reset, part 2

Source code of the lesson "Lab: persistent CLI chat with /reset, part 2" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Lab: a persistent CLI chat with /reset": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `assistant.php`
- `exercises/04-06b-lab-persistent-chat.md`

## Run

- `php assistant.php`

## Exercise

See `exercises/04-06b-lab-persistent-chat.md`.
