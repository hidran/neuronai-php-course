# Exercise: a stricter gate and a new rubric rule

Work in the `repo-auditor` project, after the audit runner is complete.

Part one: the formatter could still report a penalty without a matching finding. Extend
`App\Report\ReportGate::problems()` so that every rubric penalty needs at least one finding
with the penalty's category (`ScoreRubric::PENALTIES` holds it) that cites the penalty's
evidence id. A missing one adds the problem `missing finding for <rule>`.

Part two: add the rule `stale_history` to `ScoreRubric` (5 points, category `history`,
evidence `history-90d`): it applies when the 90-day history check is `ok` but holds no
commit. Unavailable history must never trigger it. Add the matching number to
`docs/auditor/contract.json` and to `checks/expectations.php` where it changes a score.

Check both offline, without a model: build an `EvidenceLog` by hand for a target with an
empty 90-day history, and a report object with and without the `history` finding.

Acceptance: the gate returns `missing finding for no_ci_workflow` for a library report
without its CI finding and nothing for the complete one; `stale_history` costs 5 points for
the empty window and nothing when history is unavailable; `php checks/acceptance.php` still
passes for the five fixtures. The runnable reference is `files/exercise-gate.php <project>`.
