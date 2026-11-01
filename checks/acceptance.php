<?php
declare(strict_types=1);
use App\Agents\AuditorAgent;
use App\Agents\ReportFormatter;
use App\AuditCanceled;
use App\AuditRunner;
use App\Evidence\AuditView;
use App\Evidence\EvidenceLog;
use App\Evidence\ScoreRubric;
use App\Progress;
use App\RunGuard;
use App\Target;
use App\Tools\ComposerAuditTool;
use App\Tools\ComposerManifestTool;
use App\Tools\GitHistoryTool;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Testing\FakeAIProvider;
require __DIR__ . '/../vendor/autoload.php';
$root = dirname(__DIR__);
$expected = require __DIR__ . '/expectations.php';
$collect = function (string $name, string $composer = 'composer')
    use ($root): array {
    $target = Target::fromArgument("{$root}/fixtures/{$name}");
    $log = new EvidenceLog();
    $view = AuditView::prepare($target, "{$root}/storage/views");
    $log->record('structure', 'ok', $view->facts());
    new ComposerManifestTool($target->path, $log)();
    new GitHistoryTool($target->path, $log)(90);
    new ComposerAuditTool($target->path, $log, $composer)();
    $rubric = ScoreRubric::evaluate($log);
    sort($rubric['unavailable']);
    return [
        'score' => $rubric['score'],
        'unavailable' => $rubric['unavailable'],
        'statuses' => array_column($log->all(), 'status', 'id'),
    ];
};
$passed = $total = 0;
$report = function (string $name, bool $ok, string $detail)
    use (&$passed, &$total): void {
    $total++;
    $passed += $ok ? 1 : 0;
    printf("%s %-22s %s\n", $ok ? 'PASS' : 'FAIL', $name, $detail);
};
$first = [];
foreach ($expected as $name => $want) {
    $got = $first[$name] = $collect($name);
    $report($name, $got['score'] === $want['score']
        && $got['unavailable'] === $want['unavailable'],
        "score {$got['score']}, unavailable: "
        . (implode(', ', $got['unavailable']) ?: 'none'));
}
$offline = $collect('library', "{$root}/fixtures/bin/offline-composer");
$report('advisories offline',
    $offline['unavailable'] === ['advisories'],
    "advisories {$offline['statuses']['advisories']}, never clean");
$again = $collect('library');
$report('repeat library', $again === $first['library'],
    'same evidence ids, statuses and score');
$target = Target::fromArgument("{$root}/fixtures/library");
$log = new EvidenceLog();
$view = AuditView::prepare($target, "{$root}/storage/views");
$guard = new RunGuard(deadline: microtime(true) + 60);
$guard->cancel();
$fake = new FakeAIProvider(new AssistantMessage('Collecting.'));
$runner = new AuditRunner(
    new AuditorAgent($fake, $target, $view, $log)->setThreadId('a'),
    new ReportFormatter($fake)->setThreadId('b'),
    $log, new Progress(fopen('php://memory', 'w')), $guard,
);
try {
    $runner->collect();
    $stopped = false;
} catch (AuditCanceled) {
    $stopped = true;
}
$report('canceled run', $stopped,
    'stopped at a safe boundary, no report');
echo "Acceptance: {$passed} of {$total} passed\n";
