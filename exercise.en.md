# Exercise: one more acceptance fixture

Work in the `repo-auditor` project.

Part one: add a fourth target shape under `fixtures/`, a small **CLI tool** project
(`fixtures/cli-tool`). Give it a `composer.json` with a `bin` entry and a PHP constraint,
one source file under `src/`, a `bin/` script and no test configuration. Do not put any
credential-like file in it.

Part two: run the preflight on it:

```
php auditor.php fixtures/cli-tool
```

It must print `Composer manifest: found`, `Locked dependencies: unavailable (no composer.lock)`
and `Git history: unavailable (no git metadata)`. Then run `git init` inside the fixture,
commit once, and run the preflight again: only the history line may change.

Part three: write down, using the rubric in `docs/auditor/contract.json`, the score you
expect for this fixture once the full audit exists, and which checks you expect to be
unavailable. Explain in two sentences why "no composer.lock" must not become "no
vulnerable dependencies".

Acceptance: the two preflight runs differ only in the git history line; your expected
score is 65 (no test configuration, no CI workflow) with the advisory check unavailable.
The runnable reference is `files/exercise-preflight.sh` (it builds the fixture in a
temporary copy and runs both preflights).
