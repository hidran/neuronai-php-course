<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Media\MultimodalBenchmark;
use Dotenv\Dotenv;
Dotenv::createImmutable(dirname(__DIR__))->load();
$bench = new MultimodalBenchmark(
    dirname(__DIR__) . '/fixtures/multimodal-cost',
);
$expected = [
    'heading' => 'HARBOR-731',
    'small_label' => 'COVE-592',
    'quantity' => 18,
];
$bench->expect($expected);
$cases = $bench->cases();
$bench->header();
foreach ($cases as $case) {
    $message = $bench->message($case);
    $agent = $bench->agentFor($case);
    $start = hrtime(true);
    $reply = $agent->chat($message)->getMessage();
    $tokens = $reply?->getUsage()?->inputTokens;
    $seconds = (hrtime(true) - $start) / 1e9;
    $bench->row($case, $reply, $tokens, $seconds);
}
$bench->finish();
