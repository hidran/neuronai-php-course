# Composer audit, the structured report and streaming progress

Source code of the lesson "Composer audit, the structured report and streaming progress" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Navigating the repository: FileSystemToolkit and a git log tool": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `auditor.php`
- `checks/acceptance.php`
- `checks/advisory-parser.php`
- `checks/advisory-unavailable.php`
- `checks/expectations.php`
- `checks/parse-report.php`
- `exercises/14-03-auditor-report-streaming.md`
- `exercises/exercise-gate.php`
- `exercises/exercise-test-config.php`
- `fixtures/bin/offline-composer`
- `fixtures/bin/slow-composer`
- `fixtures/composer-audit/abandoned.json`
- `fixtures/composer-audit/advisories.json`
- `fixtures/composer-audit/clean.json`
- `fixtures/composer-audit/malformed.txt`
- `src/Agents/AuditorAgent.php`
- `src/Agents/ReportFormatter.php`
- `src/AuditCanceled.php`
- `src/AuditFailed.php`
- `src/AuditRunner.php`
- `src/Evidence/AdvisoryNormalizer.php`
- `src/Evidence/ScoreRubric.php`
- `src/Progress.php`
- `src/Report/AuditReport.php`
- `src/Report/CheckStatus.php`
- `src/Report/Finding.php`
- `src/Report/ReportGate.php`
- `src/Report/ReportView.php`
- `src/RunGuard.php`
- `src/Tools/ComposerAuditTool.php`

## Run

- `php auditor.php`
- `php exercises/exercise-gate.php`
- `php exercises/exercise-test-config.php`

## Exercise

See `exercises/14-03-auditor-report-streaming.md`.
