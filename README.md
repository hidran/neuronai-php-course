# What we build and why: acceptance criteria

Source code of the lesson "What we build and why: acceptance criteria" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson; the next lessons build on it, one commit per lesson.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `.dockerignore`
- `.env.example`
- `.gitignore`
- `auditor.php`
- `bin/make-history.sh`
- `checks/verify-preflight.php`
- `compose.yaml`
- `composer.json`
- `docker/php/Dockerfile`
- `docs/auditor/contract.json`
- `examples/00-check-environment.php`
- `examples/bootstrap.php`
- `exercises/14-01-auditor-acceptance-criteria.md`
- `exercises/exercise-preflight.sh`
- `fixtures/app/.github/workflows/tests.yml`
- `fixtures/app/.gitignore`
- `fixtures/app/app/Http/Controllers/OrderController.php`
- `fixtures/app/artisan`
- `fixtures/app/composer.json`
- `fixtures/app/composer.lock`
- `fixtures/app/config/app.php`
- `fixtures/app/phpunit.xml`
- `fixtures/app/public/index.php`
- `fixtures/app/routes/web.php`
- `fixtures/app/tests/Feature/OrderTest.php`
- `fixtures/invalid-manifest/composer.json`
- `fixtures/invalid-manifest/index.php`
- `fixtures/legacy/composer.json`
- `fixtures/legacy/composer.lock`
- `fixtures/legacy/lib/Mailer.php`
- `fixtures/legacy/public/index.php`
- `fixtures/library/.gitignore`
- `fixtures/library/README.md`
- `fixtures/library/composer.json`
- `fixtures/library/composer.lock`
- `fixtures/library/phpunit.xml.dist`
- `fixtures/library/src/Slugger.php`
- `fixtures/library/src/Transliterator.php`
- `fixtures/library/tests/SluggerTest.php`
- `fixtures/no-history/composer.json`
- `fixtures/no-history/src/Clock.php`
- `fixtures/no-manifest/index.php`
- `fixtures/no-manifest/lib/helpers.php`
- `fixtures/scope/inside/notes.txt`
- `fixtures/scope/outside.txt`
- `src/Config/Provider.php`
- `src/Config/ProviderFactory.php`
- `src/Config/Settings.php`
- `src/Target.php`
- `storage/.gitkeep`

## Run

- `php auditor.php`
- `php examples/00-check-environment.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php checks/verify-preflight.php`

## Exercise

See `exercises/14-01-auditor-acceptance-criteria.md`.
