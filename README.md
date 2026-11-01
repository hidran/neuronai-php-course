# What we build and why: acceptance criteria

Source and verified exercise for Agentic AI in PHP with NeuronAI.

This branch is the repo auditor project as the lesson leaves it. Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

The audited fixtures need their short git history, which Git cannot store inside this repository: run `bash bin/make-history.sh fixtures` once. The scope fixture also needs a symbolic link that points outside it: `ln -s ../outside.txt fixtures/scope/inside/escape.txt`.

Supply your own provider credentials in `.env` for model calls. Credentials are never included.

`files/` holds the lesson's runnable checks and exercise references, run against the project folder:

- `bash files/exercise-preflight.sh`
- `php files/verify-preflight.php .`

Follow `exercise.en.md` for the practice task, its expected checks and the worked solution.
