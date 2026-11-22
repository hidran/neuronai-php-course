# Exercise: one more narrow tool for the auditor

Work in the finished `repo-auditor` project.

Add a tool that counts the licenses of the locked packages, the way the auditor's other tools work:

1. `App\Tools\ComposerLicenseTool`, a `final` class extending `NeuronAI\Tools\Tool`, named
   `read_composer_licenses`, with a short description that says what it returns. Its constructor takes the project
   root and the `EvidenceLog` as `private readonly` properties.
2. When it is called, it reads `composer.lock` (both `packages` and `packages-dev`) and records the evidence item
   `licenses` with status `ok` and three facts: `packages` (how many), `licenses` (a count per license id, sorted
   by name) and `unlicensed` (the names of packages without a license, 20 at most). With no readable lock, or a
   lock that is not valid JSON, it records `licenses` as `unavailable` with a reason, and never guesses.
3. Register it in `AuditorAgent::tools()` with `setMaxRuns(1)`, add the step "Call read_composer_licenses once."
   to the instructions, add `'read_composer_licenses' => 'licenses'` to the labels of `Progress`, and add
   `licenses` to the order of `EvidenceLog`.

The rubric does not change: licenses are information, not a penalty.

Check it offline first, without a model: call the tool directly on the fixtures, and run it once through an agent
driven by `FakeAIProvider` that asks for `read_composer_licenses`. Then run `php auditor.php fixtures/library`
with your provider. If a run stops with a TypeError from `glob_path`, the model left out that tool's optional
`directory` argument: name it in the collector's step ("Use glob_path with directory . and pattern **") and run
again.

Acceptance: for `fixtures/library` the tool records 28 packages, `BSD-3-Clause` 25 and `MIT` 3, and no unlicensed
package; for `fixtures/app`, `MIT` 4; for `fixtures/no-history` (no lock) `licenses` is unavailable; a lock with a
package that has no `license` field lists that package in `unlicensed`. `ReportGate::problems()` now returns
`check licenses must be ok` for a report that leaves the licenses check out. The real run shows
`> licenses started`, a `licenses ok` row among the checks, and still `Score: 90/100`.

The runnable reference is `php exercises/exercise-license-tool.php` (offline, no key needed, prints
`LICENSE TOOL OK`).
