<?php
declare(strict_types=1);
// Offline check of the report half of the auditor: no model (FakeAIProvider), no keys, no network (a stub composer
// on PATH prints the captured audit output). Usage: php verify-report.php <lab>. The lab needs symfony/process.
// Fixtures are rebuilt in the system temp folder from the first capstone lesson's files/. Plain checks only.
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
$first = __DIR__ . '/../../14-01-auditor-acceptance-criteria/files';
$second = __DIR__ . '/../../14-02-auditor-files-and-git/files';
require $first . '/Target.php';
foreach (['EvidenceLog', 'GitLogParser', 'AuditView', 'ComposerManifestTool', 'GitHistoryTool'] as $class) {
    require "{$second}/{$class}.php";
}
foreach (['AdvisoryNormalizer', 'ScoreRubric', 'ComposerAuditTool', 'Finding', 'CheckStatus', 'AuditReport',
    'ReportGate', 'ReportView', 'ReportFormatter', 'AuditorAgent', 'AuditFailed', 'AuditCanceled', 'RunGuard',
    'Progress', 'AuditRunner'] as $class) {
    require __DIR__ . "/{$class}.php";
}
use App\Agents\AuditorAgent;
use App\Agents\ReportFormatter;
use App\AuditCanceled;
use App\AuditFailed;
use App\AuditRunner;
use App\Evidence\AdvisoryNormalizer;
use App\Evidence\AuditView;
use App\Evidence\EvidenceLog;
use App\Evidence\ScoreRubric;
use App\Progress;
use App\Report\AuditReport;
use App\Report\CheckStatus;
use App\Report\Finding;
use App\Report\ReportGate;
use App\Report\ReportView;
use App\RunGuard;
use App\Target;
use App\Tools\ComposerAuditTool;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\StructuredOutput\Validation\Validator;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
$fail = static fn (string $why): never => throw new RuntimeException($why);
$sh = static function (string $command): string {
    exec($command . ' 2>&1', $lines, $code);
    return $code . "\n" . implode("\n", $lines);
};
// 1. Skeletons = final files minus the typed lines.
foreach (['ComposerAuditTool' => [[27, 44]], 'AuditRunner' => [[32, 53], [67, 69]]] as $name => $ranges) {
    $final = explode("\n", rtrim((string) file_get_contents(__DIR__ . "/{$name}.php"), "\n"));
    foreach (array_reverse($ranges) as [$from, $to]) {
        array_splice($final, $from - 1, $to - $from + 1);
    }
    if (implode("\n", $final) !== rtrim((string) file_get_contents(__DIR__ . "/{$name}.start.php"), "\n")) {
        $fail("{$name}.start.php is not {$name}.php minus the typed lines");
    }
}
// 2. Fixtures, history and a stub composer on PATH (prints the captured clean audit).
$tmp = sys_get_temp_dir() . '/verify-report-' . getmypid();
$sh('rm -rf ' . escapeshellarg($tmp));
mkdir($tmp . '/bin', 0o755, true);
$sh('cp -R ' . escapeshellarg($first . '/fixtures') . ' ' . escapeshellarg($tmp));
$sh('cp -R ' . escapeshellarg(__DIR__ . '/fixtures/.') . ' ' . escapeshellarg($tmp . '/fixtures'));
$fx = $tmp . '/fixtures';
$sh('bash ' . escapeshellarg($first . '/make-history.sh') . ' ' . escapeshellarg($fx) . ' --instruction');
$stub = static function (string $path, string $json, int $code): void {
    file_put_contents($path, "#!/bin/sh\ncat " . escapeshellarg($json) . "\nexit {$code}\n");
    chmod($path, 0o755);
};
$stub($tmp . '/bin/composer', $fx . '/composer-audit/clean.json', 0);
$stub($tmp . '/bin/advisories', $fx . '/composer-audit/advisories.json', 1);
putenv('PATH=' . $tmp . '/bin:' . getenv('PATH'));
// 3. The normalizer: statuses, unknown severity, abandonment apart, bounded list.
$normalizer = new AdvisoryNormalizer();
$parsed = static fn (string $file, int $code): ?array =>
    $normalizer->normalize((string) file_get_contents("{$fx}/composer-audit/{$file}"), $code);
$advisories = $parsed('advisories.json', 1);
if ($parsed('clean.json', 0)['status'] !== 'clean' || $advisories['status'] !== 'findings'
    || array_column($advisories['facts']['advisories'], 'severity') !== ['medium', 'unknown']
    || $parsed('abandoned.json', 2)['facts']['abandoned'] !== [['package' => 'swiftmailer/swiftmailer',
        'replacement' => 'symfony/mailer']]
    || $parsed('abandoned.json', 2)['facts']['advisory_count'] !== 0
    || $parsed('malformed.txt', 1) !== null || $parsed('clean.json', 255) !== null) {
    $fail('normalizer statuses');
}
$many = ['advisories' => ['a/b' => array_fill(0, 25, ['title' => 'x', 'severity' => 'high'])], 'abandoned' => []];
$capped = $normalizer->normalize(json_encode($many), 1)['facts'];
if ($capped['advisory_count'] !== 25 || count($capped['advisories']) !== 20 || $capped['truncated'] !== true) {
    $fail('advisory cap');
}
// 4. The audit tool: clean through the stub, findings with exit 1, missing lock, timeout, no usable report.
$audit = static fn (string $dir, string $composer = 'composer', float $timeout = 30.0): array =>
    json_decode(new ComposerAuditTool($dir, new EvidenceLog(), $composer, $timeout)(), true);
$offline = $fx . '/bin/offline-composer';
$results = [
    $audit($fx . '/library')['status'],
    $audit($fx . '/app', $tmp . '/bin/advisories')['status'],
    $audit($fx . '/no-history')['reason'],
    $audit($fx . '/library', $fx . '/bin/slow-composer', 1.0)['reason'],
    $audit($fx . '/library', $offline)['reason'],
];
if ($results !== ['clean', 'findings', 'no readable composer.lock, nothing locked to audit',
    'composer audit timed out or could not start', 'composer audit returned no usable report']) {
    $fail('audit tool: ' . json_encode($results));
}
// 5. Validation: words, not characters; nested findings checked by ArrayOf; an empty list allowed.
$long = implode(' ', array_fill(0, 26, 'word'));
$finding = static fn (string $text, string $severity = 'low', array $evidence = ['structure']): Finding =>
    new Finding($severity, 'ci', $text, 'Add a workflow running PHPUnit.', $evidence);
if (Validator::validate($finding($long)) === [] || Validator::validate($finding(str_repeat('a', 300) . ' b c')) !== []
    || Validator::validate($finding('No CI workflow found.', 'urgent')) === []) {
    $fail('finding rules');
}
$check = [new CheckStatus('structure', 'ok', 'structure')];
if (Validator::validate(new AuditReport('Five words in this summary.', 90, [], $check)) !== []
    || Validator::validate(new AuditReport('Five words in this summary.', 90, [$finding($long)], $check)) === []
    || Validator::validate(new AuditReport('Five words in this summary.', 101, [], $check)) === []) {
    $fail('report rules');
}
// 6. The whole run: stream with progress labels, two required checkpoints, formatter retry, gate, rendering.
$run = static function (string $name, array $collectorReplies, array $formatterReplies, ?RunGuard $guard = null)
    use ($fx, $tmp): array {
    $target = Target::fromArgument("{$fx}/{$name}");
    $out = fopen('php://memory', 'w+');
    $progress = new Progress($out);
    $log = new EvidenceLog(static fn (string $line) => $progress->line("  evidence {$line}"));
    $view = AuditView::prepare($target, $tmp . '/views');
    $log->record('structure', 'ok', $view->facts());
    $formatterFake = new FakeAIProvider(...$formatterReplies);
    $runner = new AuditRunner(
        new AuditorAgent(new FakeAIProvider(...$collectorReplies), $target, $view, $log)
            ->setThreadId("collect-{$name}")->toolMaxRuns(8),
        new ReportFormatter($formatterFake)->setThreadId("format-{$name}"),
        $log, $progress, $guard ?? new RunGuard(microtime(true) + 60),
    );
    try {
        $runner->collect();
        $result = $runner->format();
    } catch (AuditFailed | AuditCanceled $stop) {
        $result = $stop;
    }
    rewind($out);
    return [$result, stream_get_contents($out), $log, $formatterFake];
};
$tools = static fn (): ToolCallMessage => new ToolCallMessage(null, [
    new ToolCall('read_composer_manifest', 'call_manifest', []),
    new ToolCall('read_git_history', 'call_history', ['days' => 90]),
    new ToolCall('audit_composer_lock', 'call_audit', []),
    new ToolCall('glob_path', 'call_glob', ['directory' => '.', 'pattern' => '**']),
]);
$checks = [['check' => 'structure', 'status' => 'ok', 'evidence' => 'structure'],
    ['check' => 'manifest', 'status' => 'ok', 'evidence' => 'manifest'],
    ['check' => 'history', 'status' => 'ok', 'evidence' => 'history-90d'],
    ['check' => 'advisories', 'status' => 'clean', 'evidence' => 'advisories']];
$ci = ['severity' => 'medium', 'category' => 'ci', 'description' => 'No CI workflow was found in the audit view.',
    'recommendation' => 'Add a workflow that runs the PHPUnit suite.', 'evidence' => ['structure'],
    'subject' => '.github/workflows'];
$good = ['summary' => 'Tests are configured, but no CI workflow runs them.', 'score' => 90, 'findings' => [$ci],
    'checks' => $checks];
$tooLong = ['findings' => [[...$ci, 'description' => $long]]] + $good;
[$report, $progressText, $log, $formatterFake] = $run('library',
    [$tools(), new AssistantMessage('Inspected the project.')],
    [new AssistantMessage(json_encode($tooLong)), new AssistantMessage(json_encode($good))]);
if (!$report instanceof AuditReport || $report->score !== 90 || $formatterFake->getCallCount() !== 2) {
    $fail('library run: ' . ($report instanceof Throwable ? $report->getMessage() : 'retry count'));
}
foreach (['> manifest started #nifest', '< manifest collected #nifest', '  evidence advisories: running',
    '< advisories collected #_audit', '> file list started #l_glob'] as $label) {
    if (!str_contains($progressText, $label)) {
        $fail("progress label missing: {$label}\n{$progressText}");
    }
}
if (!str_contains(ReportView::render($report), '[MEDIUM] ci: No CI workflow')) {
    $fail('human view');
}
$file = $tmp . '/library.json';
file_put_contents($file, json_encode($report, JSON_UNESCAPED_SLASHES) . PHP_EOL);
$parse = $sh(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/parse-report.php') . ' '
    . escapeshellarg($file) . ' library');
if (!str_contains($parse, 'Report expectations: 3 of 3 passed')) {
    $fail("parse-report: {$parse}");
}
$wrong = ['score' => 95, 'findings' => [[...$ci, 'evidence' => ['ci-config']]]] + $good;
[$rejected] = $run('library', [$tools(), new AssistantMessage('Done.')], [new AssistantMessage(json_encode($wrong))]);
if (!$rejected instanceof AuditFailed || !str_contains($rejected->getMessage(), 'score 95 is not the rubric score 90')
    || !str_contains($rejected->getMessage(), 'unknown evidence ci-config')) {
    $fail('gate did not reject the wrong report');
}
[$missing] = $run('library', [new AssistantMessage('I did not look.')], [new AssistantMessage(json_encode($good))]);
if (!$missing instanceof AuditFailed || !str_contains($missing->getMessage(), 'missing evidence: manifest')) {
    $fail('missing checkpoints accepted');
}
$canceled = new RunGuard(microtime(true) + 60);
$canceled->cancel();
[$stopped] = $run('library', [$tools(), new AssistantMessage('Done.')], [], $canceled);
[$late] = $run('library', [$tools(), new AssistantMessage('Done.')], [], new RunGuard(microtime(true) - 1));
if (!$stopped instanceof AuditCanceled || !$late instanceof AuditFailed
    || !str_contains($late->getMessage(), 'deadline')) {
    $fail('cancellation or deadline');
}
// 7. Rubric scores of the acceptance fixtures (no model): the numbers the narration and checks rely on.
$scores = [];
foreach (['app' => $tmp . '/bin/advisories', 'library' => 'composer', 'legacy' => 'composer',
    'no-manifest' => 'composer', 'no-history' => 'composer'] as $name => $composer) {
    $target = Target::fromArgument("{$fx}/{$name}");
    $evidence = new EvidenceLog();
    $evidence->record('structure', 'ok', AuditView::prepare($target, $tmp . '/views')->facts());
    if ($name === 'legacy') {
        $stub($tmp . '/bin/abandoned', $fx . '/composer-audit/abandoned.json', 2);
        $composer = $tmp . '/bin/abandoned';
    }
    new ComposerAuditTool($target->path, $evidence, $composer)();
    $scores[$name] = ScoreRubric::evaluate($evidence)['score'];
}
if ($scores !== ['app' => 70, 'library' => 90, 'legacy' => 60, 'no-manifest' => 65, 'no-history' => 65]) {
    $fail('rubric scores: ' . json_encode($scores));
}
$expectations = require __DIR__ . '/expectations.php';
foreach ($scores as $name => $score) {
    if ($expectations[$name]['score'] !== $score) {
        $fail("expectations.php disagrees for {$name}");
    }
}
$contract = json_decode((string) file_get_contents($first . '/contract.json'), true);
foreach (ScoreRubric::PENALTIES as $rule => [$points]) {
    if ($contract['rubric']['penalties'][$rule] !== $points) {
        $fail("contract.json and ScoreRubric disagree on {$rule}");
    }
}
$sh('rm -rf ' . escapeshellarg($tmp));
echo 'REPORT OK' . PHP_EOL;
