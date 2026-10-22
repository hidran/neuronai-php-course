<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Media\MultimodalBenchmark;
use Dotenv\Dotenv;
Dotenv::createImmutable(dirname(__DIR__))->load();
$bench = new MultimodalBenchmark(
    dirname(__DIR__) . '/fixtures/multimodal-cost',
);
// Define task correctness here.
$cases = $bench->cases();
$bench->header();
foreach ($cases as $case) {
    $message = $bench->message($case);
    $agent = $bench->agentFor($case);
    // Measure this isolated request here.
}
$bench->finish();
