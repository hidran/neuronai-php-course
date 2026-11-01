<?php
declare(strict_types=1);
// Reference solution of the exercise, offline: php exercise-gate.php <repo-auditor project>
$project = $argv[1] ?? throw new RuntimeException('project path');
require $project . '/vendor/autoload.php';
use App\Evidence\EvidenceLog;
use App\Evidence\ScoreRubric;
use App\Report\AuditReport;
use App\Report\CheckStatus;
use App\Report\Finding;
use App\Report\ReportGate;
function missingFindings(AuditReport $report, array $rubric): array
{
    $problems = [];
    foreach ($rubric['penalties'] as $penalty) {
        $found = array_find($report->findings, fn (Finding $finding): bool =>
            $finding->category === $penalty['category']
            && in_array($penalty['evidence'], $finding->evidence, true));
        if ($found === null) {
            $problems[] = "missing finding for {$penalty['rule']}";
        }
    }
    return $problems;
}
function staleHistory(EvidenceLog $log): int
{
    $history = $log->get('history-90d');
    return ($history['status'] ?? '') === 'ok'
        && ($history['facts']['commits'] ?? null) === [] ? 5 : 0;
}
$log = new EvidenceLog();
$log->record('structure', 'ok', ['test_config' => true, 'ci_workflow' => false]);
$log->record('history', 'ok', ['window_days' => 90, 'commits' => []], 'history-90d');
$rubric = ScoreRubric::evaluate($log);
$checks = [new CheckStatus('structure', 'ok', 'structure'),
    new CheckStatus('history', 'ok', 'history-90d')];
$ci = new Finding('medium', 'ci', 'No CI workflow was found.', 'Add a workflow running PHPUnit.',
    ['structure']);
$without = new AuditReport('The project has tests but no CI.', $rubric['score'], [], $checks);
$with = new AuditReport('The project has tests but no CI.', $rubric['score'], [$ci], $checks);
$results = [
    missingFindings($without, $rubric),
    missingFindings($with, $rubric),
    ReportGate::problems($with, $log, $rubric),
    staleHistory($log),
];
$unavailable = new EvidenceLog();
$unavailable->unavailable('history', 'the target has no git metadata');
$results[] = staleHistory($unavailable);
echo json_encode($results), PHP_EOL;
$ok = $results === [['missing finding for no_ci_workflow'], [], [], 5, 0];
echo $ok ? "EXERCISE OK\n" : "EXERCISE FAILED\n";
exit($ok ? 0 : 1);
