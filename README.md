# Navigating the repository: FileSystemToolkit and a git log tool

Source code of the lesson "Navigating the repository: FileSystemToolkit and a git log tool" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "What we build and why: acceptance criteria": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `auditor.php`
- `checks/git-history.php`
- `checks/manifest-tool.php`
- `checks/scope-check.php`
- `composer.json`
- `exercises/14-02-auditor-files-and-git.md`
- `exercises/exercise-test-config.php`
- `src/Agents/AuditorAgent.php`
- `src/Evidence/AuditView.php`
- `src/Evidence/EvidenceLog.php`
- `src/Evidence/GitLogParser.php`
- `src/Tools/ComposerManifestTool.php`
- `src/Tools/GitHistoryTool.php`

## Run

- `php auditor.php`
- `php exercises/exercise-test-config.php`

## Exercise

See `exercises/14-02-auditor-files-and-git.md`.
