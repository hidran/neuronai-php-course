# AI-assisted development: NeuronAI skills and docs through MCP

Source code of the lesson "AI-assisted development: NeuronAI skills and docs through MCP" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "The ecosystem: Inspector, Maestro live, Neuron Hub, Studio and Symfony": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/61-review-change.php`
- `examples/61-skill-targets.php`
- `examples/61-support-ask.php`
- `examples/exercise-question-guard.php`
- `examples/services.php`
- `exercises/15-03-ai-development-skills-mcp.md`
- `prompts/support-command.txt`
- `src/Support/Container.php`
- `src/Support/SupportAsk.php`
- `src/Support/SupportQuestion.php`
- `tests/SupportAskTest.php`

## Run

- `php examples/61-review-change.php`
- `php examples/61-skill-targets.php`
- `php examples/61-support-ask.php`
- `php examples/exercise-question-guard.php`

## Exercise

See `exercises/15-03-ai-development-skills-mcp.md`.
