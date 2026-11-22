# Version strategy: recognizing v3 code, upgrading to 4.x, following minor releases

Source code of the lesson "Version strategy: recognizing v3 code, upgrading to 4.x, following minor releases" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Choosing knowledge mechanisms, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/exercise-upgrade.php`
- `examples/verify-upgrade.php`
- `exercises/15-01-version-strategy.md`
- `fixtures/legacy/old-callers.php`
- `fixtures/legacy/old-resume.php`
- `fixtures/legacy/old-tool.php`
- `fixtures/legacy/prerelease-agent.php`
- `src/Upgrade/ApprovalResume.php`
- `src/Upgrade/AssistantCalls.php`
- `src/Upgrade/NotesAgent.php`
- `src/Upgrade/StreamedAnswer.php`
- `src/Upgrade/WordCountTool.php`
- `tests/UpgradeContractTest.php`

## Run

- `php examples/exercise-upgrade.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-upgrade.php`

## Exercise

See `exercises/15-01-version-strategy.md`.
