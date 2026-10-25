<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Rag\HeadingSplitter;
use App\Rag\KeywordEmbeddings;
use App\Rag\RetrievalEval;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\PostProcessor\FixedThresholdPostProcessor;
use NeuronAI\RAG\Retrieval\RetrievalInterface;
use NeuronAI\RAG\Retrieval\SimilarityRetrieval;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronAI\Chat\Messages\Message;
$rag = require __DIR__ . '/rag-bootstrap.php';
$root = dirname(__DIR__);
$offline = in_array('--offline', $argv, true);
$embeddings = $offline ? new KeywordEmbeddings() : $rag->embeddings;
$chunks = FileDataLoader::for($root . '/fixtures/docs')
    ->withSplitter(new HeadingSplitter(maxLength: 400))
    ->getDocuments();
foreach ($chunks as $chunk) {
    $chunk->setSourceName(basename($chunk->getSourceName()));
}
$store = new MemoryVectorStore(topK: 4);
$store->addDocuments($embeddings->embedDocuments($chunks));
$questions = require $root . '/fixtures/quality/questions.php';
$eval = new RetrievalEval($questions);
$plain = new SimilarityRetrieval($store, $embeddings);
$thresholded = new class($store, $embeddings, 0.50)
    implements RetrievalInterface {
    public function __construct(
        private readonly VectorStoreInterface $store,
        private readonly EmbeddingsProviderInterface $embeddings,
        private readonly float $threshold,
    ) {
    }
    #[\Override]
    public function retrieve(Message $query, $filters = null): array
    {
        $found = new SimilarityRetrieval($this->store,
            $this->embeddings)->retrieve($query, $filters);
        return new FixedThresholdPostProcessor($this->threshold)
            ->process($query, $found);
    }
};
$setups = ['plain' => $plain, 'threshold' => $thresholded];
foreach ($setups as $name => $r) {
    $result = $eval->run($r);
    echo "== {$name}" . PHP_EOL;
    foreach ($result['rows'] as $row) {
        echo '  ' . $row . PHP_EOL;
    }
    printf("hit rate %d/%d, refused %d\n", $result['hits'],
        $result['total'], $result['refused']);
}
echo 'Retrieval evaluation complete.' . PHP_EOL;
