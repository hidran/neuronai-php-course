<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
use App\Config\EmbeddingsFactory;
use App\Config\EmbeddingsSettings;
use App\Config\VectorStoreFactory;
use App\Config\VectorStoreSettings;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\Filter\Filter;
use NeuronAI\RAG\VectorStore\Filter\FilterGroup;
use NeuronAI\RAG\VectorStore\SearchRequest;
$root = dirname(__DIR__);
$embeddings = new EmbeddingsFactory(EmbeddingsSettings::fromEnv($_ENV))
    ->create();
$stores = new VectorStoreFactory(
    VectorStoreSettings::fromEnv($_ENV, $root));
$store = $stores->create('support-demo');
echo 'Store: ' . $store::class . PHP_EOL;
$texts = [
    'support-hours.md' => 'Support answers chats within two hours.',
    'support-login.md' => 'Locked accounts reset at the sign in page.',
    'billing.md' => 'Invoices are emailed on the first of the month.',
];
$documents = [];
foreach ($texts as $source => $text) {
    $documents[] = new Document($text)
        ->setSourceType('files')->setSourceName($source);
}
$store->addDocuments($embeddings->embedDocuments($documents));
printf("Stored: %d documents\n", count($documents));
$question = 'How fast does support answer a chat?';
$request = new SearchRequest(
    embedding: $embeddings->embedText($question), topK: 2);
foreach ($store->search($request) as $document) {
    printf("%s | %.3f | %s\n", $document->getSourceName(),
        $document->getScore(), $document->getContent());
}
echo "Search complete.\n";
// A source changed: delete its old chunks, then add the new version.
$store->delete(FilterGroup::and(
    Filter::eq('sourceType', 'files'),
    Filter::eq('sourceName', 'support-hours.md'),
));
$store->addDocuments($embeddings->embedDocuments([
    new Document('Support answers chats within one hour.')
        ->setSourceType('files')->setSourceName('support-hours.md'),
]));
$top = array_first([...$store->search($request)]);
printf("After reindex: %s | %s\n", $top->getSourceName(),
    $top->getContent());
echo "Reindex complete.\n";
