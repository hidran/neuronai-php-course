<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
use App\Config\EmbeddingsFactory;
use App\Config\EmbeddingsSettings;
use App\Config\VectorStoreFactory;
use App\Config\VectorStoreSettings;
use NeuronAI\RAG\VectorStore\SearchRequest;
$root = dirname(__DIR__);
$embeddings = new EmbeddingsFactory(EmbeddingsSettings::fromEnv($_ENV))
    ->create();
$stores = new VectorStoreFactory(
    VectorStoreSettings::fromEnv($_ENV, $root));
$store = $stores->create('support-demo');
$request = new SearchRequest(
    embedding: $embeddings->embedText('When are invoices emailed?'),
    topK: 2);
echo 'Store: ' . $store::class . PHP_EOL;
foreach ($store->search($request) as $document) {
    printf("%s | %.3f | %s\n", $document->getSourceName(),
        $document->getScore(), $document->getContent());
}
echo "Separate query process complete.\n";
