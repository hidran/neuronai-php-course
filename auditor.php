<?php
declare(strict_types=1);
use App\Agents\AuditorAgent;
use App\Config\ProviderFactory;
use App\Evidence\AuditView;
use App\Evidence\EvidenceLog;
use App\Target;
use NeuronAI\Agent\Observability\ToolCalling;
use NeuronAI\Chat\Messages\UserMessage;
require __DIR__ . '/vendor/autoload.php';
try {
    $target = Target::fromArgument($argv[1] ?? null);
} catch (InvalidArgumentException $error) {
    fwrite(STDERR, "Error: {$error->getMessage()}\n");
    fwrite(STDERR, "Usage: php auditor.php <directory> [--evidence]\n");
    exit(2);
}
echo "Target: {$target->label}\n";
if (!in_array('--evidence', $argv, true)) {
    echo "Preflight only: add --evidence to collect evidence\n";
    exit(0);
}
$settings = require __DIR__ . '/examples/bootstrap.php';
$log = new EvidenceLog(
    static fn (string $line) => fwrite(STDERR, "  evidence {$line}\n"),
);
$view = AuditView::prepare($target, __DIR__ . '/storage/views');
$log->record('structure', 'ok', $view->facts());
$provider = new ProviderFactory($settings)->create();
$agent = new AuditorAgent($provider, $target, $view, $log)
    ->setThreadId("evidence-{$target->label}")
    ->toolMaxRuns(8);
$agent->subscribe(ToolCalling::class,
    static fn (ToolCalling $event) => fwrite(STDERR,
        "tool {$event->tool->getName()}\n"));
$agent->chat(new UserMessage('Collect the evidence for this audit.'));
foreach ($log->all() as $id => $item) {
    printf("  %-12s %s\n", $id, $item['status']);
}
$file = "storage/evidence/{$target->label}.json";
@mkdir(__DIR__ . '/storage/evidence', 0o755, true);
file_put_contents(__DIR__ . "/{$file}", json_encode($log->all(),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
echo "Evidence saved: {$file}\n";
