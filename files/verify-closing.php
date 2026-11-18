<?php
declare(strict_types=1);
// Offline check of the closing lecture: the finished repo auditor in the lab, run end to end with no model
// (FakeAIProvider), no keys and no network (a stub composer on PATH prints the capstone's captured clean audit).
// Usage: php verify-closing.php <repo-auditor lab>. Uses the lab's own classes (its autoloader) and fixtures (the
// library's git history built by prepare.sh); writes only to the system temp folder. Plain checks only.
// It covers what the lecture shows and says: the agent's steps and narrow tools (scene 2), the progress lines on
// standard error, the four checks, the score the rubric computes, the human view and the saved-report label
// (scene 3), the gate rejecting a report that disagrees with the evidence.
$lab = rtrim($argv[1] ?? throw new RuntimeException('lab path'), '/');
$capstone = __DIR__ . '/../../14-03-auditor-report-streaming/files';
require $lab . '/vendor/autoload.php';
use App\Agents\AuditorAgent;
use App\Agents\ReportFormatter;
use App\AuditFailed;
use App\AuditRunner;
use App\Evidence\AuditView;
use App\Evidence\EvidenceLog;
use App\Progress;
use App\Report\AuditReport;
use App\Report\ReportView;
use App\RunGuard;
use App\Target;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
$fail = static fn (string $why): never => throw new RuntimeException($why);
// 1. The lab holds the capstone's final files, not the skeletons that lesson types into.
foreach (['ComposerAuditTool.php' => 'src/Tools/ComposerAuditTool.php', 'AuditRunner.php' => 'src/AuditRunner.php',
    'AuditorAgent.php' => 'src/Agents/AuditorAgent.php', 'auditor.php' => 'auditor.php',
    'Progress.php' => 'src/Progress.php', 'ReportView.php' => 'src/Report/ReportView.php'] as $from => $to) {
    if (file_get_contents("{$capstone}/{$from}") !== file_get_contents("{$lab}/{$to}")) {
        $fail("{$to} is not the capstone's final version");
    }
}
// 2. What scene 2 points at: the steps (lines 42-48) and the narrow tools with their run limits (58-70).
$agent = file("{$lab}/src/Agents/AuditorAgent.php", FILE_IGNORE_NEW_LINES);
$steps = implode("\n", array_slice($agent, 41, 7));
$tools = implode("\n", array_slice($agent, 57, 13));
foreach (['read_composer_manifest once', 'read_git_history once', 'audit_composer_lock once'] as $step) {
    if (!str_contains($steps, $step)) {
        $fail("instruction step missing from lines 42-48: {$step}");
    }
}
foreach (['->only([', 'ComposerManifestTool::make', 'GitHistoryTool::make', 'ComposerAuditTool::make',
    '->setMaxRuns(1)'] as $part) {
    if (!str_contains($tools, $part)) {
        $fail("tool list lines 58-70 lack {$part}");
    }
}
if (!str_contains((string) file_get_contents("{$lab}/auditor.php"),
    'echo "Report saved: storage/reports/{$target->label}.json\n";')) {
    $fail('auditor.php no longer prints the label scene 3 waits for');
}
// 3. A stub composer on PATH: the captured clean audit, exit 0.
$tmp = sys_get_temp_dir() . '/verify-closing-' . getmypid();
exec('rm -rf ' . escapeshellarg($tmp));
mkdir($tmp . '/bin', 0o755, true);
file_put_contents($tmp . '/bin/composer', "#!/bin/sh\ncat "
    . escapeshellarg("{$capstone}/fixtures/composer-audit/clean.json") . "\nexit 0\n");
chmod($tmp . '/bin/composer', 0o755);
putenv('PATH=' . $tmp . '/bin:' . getenv('PATH'));
// 4. The run auditor.php does, wired the same way, with fake providers for the collector and the formatter.
$run = static function (array $formatterReplies) use ($lab, $tmp): array {
    $target = Target::fromArgument("{$lab}/fixtures/library");
    $out = fopen('php://memory', 'w+');
    $progress = new Progress($out);
    $log = new EvidenceLog(static fn (string $line) => $progress->line("  evidence {$line}"));
    $view = AuditView::prepare($target, $tmp . '/views');
    $log->record('structure', 'ok', $view->facts());
    $calls = new ToolCallMessage(null, [
        new ToolCall('read_composer_manifest', 'call_manifest', []),
        new ToolCall('read_git_history', 'call_history', ['days' => 90]),
        new ToolCall('audit_composer_lock', 'call_audit', []),
        new ToolCall('glob_path', 'call_glob', ['directory' => '.', 'pattern' => '**']),
    ]);
    $runner = new AuditRunner(
        new AuditorAgent(new FakeAIProvider($calls, new AssistantMessage('Inspected the project.')),
            $target, $view, $log)->setThreadId('collect-library')->toolMaxRuns(8),
        new ReportFormatter(new FakeAIProvider(...$formatterReplies))->setThreadId('format-library'),
        $log, $progress, new RunGuard(microtime(true) + 60),
    );
    try {
        $runner->collect();
        $result = $runner->format();
    } catch (AuditFailed $stop) {
        $result = $stop;
    }
    rewind($out);
    return [$result, (string) stream_get_contents($out), $log];
};
$checks = [['check' => 'structure', 'status' => 'ok', 'evidence' => 'structure'],
    ['check' => 'manifest', 'status' => 'ok', 'evidence' => 'manifest'],
    ['check' => 'history', 'status' => 'ok', 'evidence' => 'history-90d'],
    ['check' => 'advisories', 'status' => 'clean', 'evidence' => 'advisories']];
$ci = ['severity' => 'medium', 'category' => 'ci', 'description' => 'No CI workflow was found in the audit view.',
    'recommendation' => 'Add a workflow that runs the PHPUnit suite.', 'evidence' => ['structure'],
    'subject' => '.github/workflows'];
$good = ['summary' => 'Tests are configured, but no CI workflow runs them.', 'score' => 90, 'findings' => [$ci],
    'checks' => $checks];
[$report, $progressText, $log] = $run([new AssistantMessage(json_encode($good))]);
if (!$report instanceof AuditReport) {
    $fail('library run: ' . ($report instanceof Throwable ? $report->getMessage() : 'no report'));
}
foreach (['  evidence structure: ok', '> manifest started #nifest', '< manifest collected #nifest',
    '> history started #istory', '  evidence advisories: running', '  evidence advisories: clean',
    '< advisories collected #_audit', '> file list started #l_glob'] as $label) {
    if (!str_contains($progressText, $label)) {
        $fail("progress label missing: {$label}\n{$progressText}");
    }
}
$human = ReportView::render($report);
foreach (['Score: 90/100', '  structure    ok', '  manifest     ok', '  history      ok', '  advisories   clean',
    '[MEDIUM] ci: No CI workflow', '    -> Add a workflow that runs the PHPUnit suite. (structure)'] as $line) {
    if (!str_contains($human, $line)) {
        $fail("human view lacks: {$line}\n{$human}");
    }
}
// 5. The gate: a report whose score and evidence disagree with the log is never published.
$wrong = ['score' => 100, 'findings' => [[...$ci, 'evidence' => ['ci-config']]]] + $good;
[$rejected] = $run([new AssistantMessage(json_encode($wrong))]);
if (!$rejected instanceof AuditFailed || !str_contains($rejected->getMessage(), 'score 100 is not the rubric score 90')
    || !str_contains($rejected->getMessage(), 'unknown evidence ci-config')) {
    $fail('the gate accepted a report that disagrees with the evidence');
}
exec('rm -rf ' . escapeshellarg($tmp));
if (is_dir("{$lab}/storage")) {
    $fail('the check wrote into the lab');
}
echo 'CLOSING OK' . PHP_EOL;
