<?php
declare(strict_types=1);
use App\Agents\AuditorAgent;
use App\Agents\ReportFormatter;
use App\AuditCanceled;
use App\AuditFailed;
use App\AuditRunner;
use App\Config\ProviderFactory;
use App\Evidence\AuditView;
use App\Evidence\EvidenceLog;
use App\Progress;
use App\Report\ReportView;
use App\RunGuard;
use App\Target;
ini_set('display_errors', 'stderr');
require __DIR__ . '/vendor/autoload.php';
$options = array_slice($argv, 2);
try {
    $target = Target::fromArgument($argv[1] ?? null);
} catch (InvalidArgumentException $error) {
    fwrite(STDERR, "Error: {$error->getMessage()}\n");
    fwrite(STDERR, "Usage: php auditor.php <directory> [--json]\n");
    exit(2);
}
$settings = require __DIR__ . '/examples/bootstrap.php';
$progress = new Progress();
$log = new EvidenceLog(
    static fn (string $line) => $progress->line("  evidence {$line}"),
);
$view = AuditView::prepare($target, __DIR__ . '/storage/views');
$log->record('structure', 'ok', $view->facts());
$factory = new ProviderFactory($settings);
$collector = new AuditorAgent($factory->create(), $target, $view,
    $log)
    ->setThreadId("collect-{$target->label}")
    ->toolMaxRuns(8);
$formatter = new ReportFormatter($factory->create())
    ->setThreadId("format-{$target->label}");
$guard = new RunGuard(deadline: microtime(true) + 180);
pcntl_async_signals(true);
pcntl_signal(SIGINT, static fn () => $guard->cancel());
$runner = new AuditRunner($collector, $formatter, $log, $progress,
    $guard);
$save = static function (string $file, mixed $data): void {
    @mkdir(dirname(__DIR__ . "/{$file}"), 0o755, true);
    file_put_contents(__DIR__ . "/{$file}", json_encode($data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR));
};
try {
    $runner->collect();
    $report = $runner->format();
} catch (AuditCanceled | AuditFailed $stop) {
    $save("storage/evidence/{$target->label}.incomplete.json",
        ['complete' => false, 'evidence' => $log->all()]);
    $canceled = $stop instanceof AuditCanceled;
    $progress->line($canceled ? 'Canceled: no report published.'
        : "Audit failed: {$stop->getMessage()}");
    exit($canceled ? 130 : 1);
}
if (in_array('--json', $options, true)) {
    $progress->line('Report ready: one JSON document on standard output');
    echo json_encode($report, JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR), PHP_EOL;
    exit(0);
}
$save("storage/reports/{$target->label}.json", $report);
$save("storage/evidence/{$target->label}.json", $log->all());
echo ReportView::render($report);
echo "Report saved: storage/reports/{$target->label}.json\n";
