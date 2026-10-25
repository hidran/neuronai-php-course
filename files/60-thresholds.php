<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Rag\HeadingSplitter;
use App\Rag\ReturnsPolicyAgent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\PostProcessor\AdaptiveThresholdPostProcessor;
use NeuronAI\RAG\PostProcessor\FixedThresholdPostProcessor;
use NeuronAI\RAG\Retrieval\SimilarityRetrieval;
$rag = require __DIR__ . '/rag-bootstrap.php';
$store = $rag->stores->create('docs-quality');
$agent = ReturnsPolicyAgent::make($rag->provider, $rag->embeddings,
    $store);
if (($argv[1] ?? '') === 'index') {
    $root = dirname(__DIR__) . '/fixtures/docs';
    $chunks = FileDataLoader::for($root)
        ->withSplitter(new HeadingSplitter(maxLength: 400))
        ->getDocuments();
    foreach ($chunks as $chunk) {
        $chunk->setSourceName(basename($chunk->getSourceName()));
    }
    $agent->reindexBySource($chunks);
    echo 'Indexed: ' . count($chunks) . ' chunks' . PHP_EOL;
    exit;
}
$question = new UserMessage(
    $argv[1] ?? 'Can Tidepool export reports to Excel?');
$retrieval = new SimilarityRetrieval($store, $rag->embeddings);
$found = $retrieval->retrieve($question);
echo "Question: {$question->getContent()}" . PHP_EOL;
foreach ($found as $document) {
    printf("  raw   %.2f %-10s %s\n", $document->getScore(),
        $document->getSourceName(),
        $document->getMetadata()['heading_path'] ?? '');
}
$processors = [
    'fixed 0.50' => new FixedThresholdPostProcessor(0.50),
    'adaptive' => new AdaptiveThresholdPostProcessor(),
];
foreach ($processors as $label => $processor) {
    $kept = $processor->process($question, $found);
    printf("%-11s keeps %d of %d\n", $label, count($kept),
        count($found));
}
echo 'Threshold check complete.' . PHP_EOL;
