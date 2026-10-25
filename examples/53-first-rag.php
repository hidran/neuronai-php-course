<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Rag\ReturnsPolicyAgent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\UniqueIdGenerator;
$rag = require __DIR__ . '/rag-bootstrap.php';
$mode = $argv[1] ?? 'retrieve';
$agent = ReturnsPolicyAgent::make($rag->provider, $rag->embeddings,
    $rag->stores->create('cedar-support'));
if ($mode === 'index') {
    $path = dirname(__DIR__) . '/fixtures/rag/cedar-policy.txt';
    $documents = FileDataLoader::for($path)->getDocuments();
    foreach ($documents as $document) {
        $document->setSourceName(basename($document->getSourceName()));
    }
    $agent->reindexBySource($documents);
    echo 'Indexing completed: ' . count($documents) . ' chunk.'
        . PHP_EOL;
    exit;
}
$question = $mode === 'unsupported'
    ? 'Does Cedar Support provide free repairs for five years?'
    : 'How long can I return a damaged item and what must I provide?';
if ($mode === 'retrieve') {
    $documents = $agent->resolveRetrieval()
        ->retrieve(new UserMessage($question));
    foreach ($documents as $document) {
        printf("Source: %s (score %.3f)\n", $document->getSourceName(),
            $document->getScore());
        echo $document->getContent() . PHP_EOL;
    }
    echo 'Retrieval completed: ' . count($documents) . ' chunk.'
        . PHP_EOL;
    exit;
}
if (!in_array($mode, ['ask', 'unsupported'], true)) {
    throw new InvalidArgumentException('Unknown RAG demo mode.');
}
$state = $agent->setThreadId(UniqueIdGenerator::generateId('rag_'))
    ->chat(new UserMessage($question));
echo $state->getMessage()?->getContent() . PHP_EOL;
echo "Answered by: {$rag->settings->provider->value}" . PHP_EOL;
echo 'Answer completed: ' . $mode . PHP_EOL;
