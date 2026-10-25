<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Rag\ReturnsPolicyAgent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\DataLoader\FileDataLoader;
$rag = require __DIR__ . '/rag-bootstrap.php';
$path = dirname(__DIR__) . '/fixtures/rag/dispatch-policy.txt';
$documents = FileDataLoader::for($path)->getDocuments();
foreach ($documents as $document) {
    $document->setSourceName(basename($document->getSourceName()));
}
ReturnsPolicyAgent::make($rag->provider, $rag->embeddings,
    $rag->stores->create('dispatch'))->reindexBySource($documents);
$fresh = ReturnsPolicyAgent::make($rag->provider, $rag->embeddings,
    $rag->stores->create('dispatch'));
$hits = $fresh->resolveRetrieval()->retrieve(
    new UserMessage('How quickly does an order leave the warehouse?'));
if ($hits === [] || $hits[0]->getSourceName() !== 'dispatch-policy.txt'
    || !str_contains($hits[0]->getContent(), '3 business days')) {
    throw new RuntimeException('Persistent retrieval exercise failed.');
}
printf("Dispatch source retrieved with %d dimensions.\n",
    count($hits[0]->getEmbedding() ?? []));
echo 'First RAG exercise passed.' . PHP_EOL;
