<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Rag\HeadingSplitter;
use App\Rag\KeywordEmbeddings;
use App\Rag\RetrievalEval;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\PostProcessor\AdaptiveThresholdPostProcessor;
use NeuronAI\RAG\PostProcessor\FixedThresholdPostProcessor;
use NeuronAI\RAG\PostProcessor\PostProcessorInterface;
use NeuronAI\RAG\Retrieval\RetrievalInterface;
use NeuronAI\RAG\Retrieval\SimilarityRetrieval;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
$root = dirname(__DIR__);
$live = in_array('--live', $argv, true);
$embeddings = $live
    ? (require __DIR__ . '/rag-bootstrap.php')->embeddings
    : new KeywordEmbeddings();
$chunks = FileDataLoader::for($root . '/fixtures/docs')
    ->withSplitter(new HeadingSplitter(maxLength: 400))
    ->getDocuments();
foreach ($chunks as $chunk) {
    $chunk->setSourceName(basename($chunk->getSourceName()));
}
$store = new MemoryVectorStore(topK: 4);
$store->addDocuments($embeddings->embedDocuments($chunks));
$questions = require $root . '/fixtures/quality/questions.php';
$questions['sync'] = ['How often does Tidepool sync?', 'setup.md'];
$eval = new RetrievalEval($questions);
$plain = new SimilarityRetrieval($store, $embeddings);
$after = static fn (PostProcessorInterface $post): RetrievalInterface
    => new readonly class($plain, $post) implements RetrievalInterface {
        public function __construct(
            private RetrievalInterface $inner,
            private PostProcessorInterface $post,
        ) {
        }
        #[\Override]
        public function retrieve(Message $query, $filters = null): array
        {
            return $this->post->process($query,
                $this->inner->retrieve($query, $filters));
        }
    };
$setups = [
    'plain' => $plain,
    'fixed 0.45' => $after(new FixedThresholdPostProcessor(0.45)),
    'fixed 0.50' => $after(new FixedThresholdPostProcessor(0.50)),
    'fixed 0.60' => $after(new FixedThresholdPostProcessor(0.60)),
    'adaptive' => $after(new AdaptiveThresholdPostProcessor()),
];
foreach ($setups as $name => $retrieval) {
    $result = $eval->run($retrieval);
    echo "== {$name}" . PHP_EOL;
    foreach ($result['rows'] as $row) {
        echo '  ' . $row . PHP_EOL;
    }
    printf("hit rate %d/%d, refused %d\n", $result['hits'],
        $result['total'], $result['refused']);
    if ($result['total'] !== 6) {
        throw new RuntimeException('expected six answerable questions');
    }
    if ($name === 'adaptive' && $result['refused'] !== 0) {
        throw new RuntimeException('adaptive should never refuse');
    }
}
echo 'Retrieval exercise complete.' . PHP_EOL;
