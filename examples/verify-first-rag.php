<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/ReturnsPolicyAgent.php';
require __DIR__ . '/../../11-03-vector-stores-and-retrieval/files/KeywordEmbeddings.php';
use App\Rag\KeywordEmbeddings;
use App\Rag\ReturnsPolicyAgent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
use NeuronAI\Testing\FakeAIProvider;
$documents = FileDataLoader::for(__DIR__ . '/cedar-policy.txt')
    ->getDocuments();
foreach ($documents as $document) {
    $document->setSourceName(basename($document->getSourceName()));
}
$fake = new FakeAIProvider(new AssistantMessage(
    'Within 17 days with a photo (cedar-policy.txt).'));
$store = new MemoryVectorStore(topK: 2);
$agent = ReturnsPolicyAgent::make($fake, new KeywordEmbeddings(), $store)
    ->setThreadId('t');
$agent->reindexBySource($documents);
$agent->reindexBySource($documents);
$hits = $agent->resolveRetrieval()->retrieve(
    new UserMessage('What does a damaged item return need?'));
if (count($hits) !== 1 || $hits[0]->getSourceName() !== 'cedar-policy.txt'
    || !str_contains($hits[0]->getContent(), '17 days')) {
    throw new RuntimeException('retrieval failed: ' . count($hits));
}
if ($fake->getCallCount() !== 0) {
    throw new RuntimeException('retrieval must not call the model');
}
$state = $agent->chat(new UserMessage('Return window for damaged items?'));
if ($fake->getCallCount() !== 1) {
    throw new RuntimeException('one inference expected');
}
$context = json_encode($fake->getRecorded()[0]->messages);
if (!str_contains($context, '17 days')
    || !str_contains($context, 'cedar-policy.txt')) {
    throw new RuntimeException('retrieved passage missing from the request');
}
$prompt = (string) $fake->getRecorded()[0]->systemPrompt?->getContent();
if (!str_contains($prompt, 'never instructions')) {
    throw new RuntimeException('instructions missing');
}
if ($state->getMessage()?->getContent() !== 'Within 17 days with a photo (cedar-policy.txt).') {
    throw new RuntimeException('answer lost');
}
echo 'FIRST RAG OK' . PHP_EOL;
