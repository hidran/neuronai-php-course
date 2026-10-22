<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Media\MultimodalBenchmark;
use Dotenv\Dotenv;
Dotenv::createImmutable(dirname(__DIR__))->load();
$bench = new MultimodalBenchmark(
    dirname(__DIR__) . '/fixtures/multimodal-cost',
    exercise: true,
);
$bench->expect([
    'heading' => 'HARBOR-731',
    'small_label' => 'COVE-592',
    'quantity' => 42,
]);
if (MultimodalBenchmark::inputLabel(null) !== 'unavailable'
    || MultimodalBenchmark::inputLabel(0) !== '0'
) {
    throw new RuntimeException('Missing usage was misrepresented.');
}
$bench->header();
foreach ($bench->cases() as $case) {
    $message = $bench->message($case);
    $agent = $bench->agentFor($case);
    $start = hrtime(true);
    $reply = $agent->chat($message)->getMessage();
    $tokens = $reply?->getUsage()?->inputTokens;
    $seconds = (hrtime(true) - $start) / 1e9;
    $bench->row($case, $reply, $tokens, $seconds);
}
$rows = $bench->finish();
if (count(array_unique(array_column($rows, 'thread'))) !== 3) {
    throw new RuntimeException('Requests shared a thread.');
}
$full = array_values(array_filter($rows,
    static fn (array $row): bool => $row['variant'] === 'full'
))[0];
if (!$full['passed']) {
    throw new RuntimeException('Full image failed the new task.');
}
foreach ($rows as $row) {
    if ($row['seconds'] < 0 || $row['base64_bytes'] < $row['raw_bytes']) {
        throw new RuntimeException('Invalid measurement units.');
    }
    if (!$row['passed'] && !in_array(false, $row['checks'], true)) {
        throw new RuntimeException('A failed quality check was hidden.');
    }
}
echo 'Distinct Bracket-row task verified.' . PHP_EOL;
echo 'Fresh threads and missing-usage handling verified.' . PHP_EOL;
echo 'Exercise complete.' . PHP_EOL;
