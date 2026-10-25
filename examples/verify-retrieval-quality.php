<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/RetrievalEval.php';
require __DIR__ . '/HeadingSplitter.php';
require __DIR__ . '/KeywordEmbeddings.php';
use App\Rag\HeadingSplitter;
use App\Rag\KeywordEmbeddings;
use App\Rag\RetrievalEval;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\PostProcessor\AdaptiveThresholdPostProcessor;
use NeuronAI\RAG\PostProcessor\FixedThresholdPostProcessor;
use NeuronAI\RAG\Retrieval\SimilarityRetrieval;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
$docs = __DIR__ . '/fixtures/docs';
$embeddings = new KeywordEmbeddings();
// Indexed as 60-thresholds.php and 60-retrieval-eval.php index: one chunk per section.
$chunks = FileDataLoader::for($docs)
    ->withSplitter(new HeadingSplitter(maxLength: 400))->getDocuments();
if (count($chunks) !== 11) {
    throw new RuntimeException('expected 11 chunks, got ' . count($chunks));
}
foreach ($chunks as $chunk) {
    $chunk->setSourceName(basename($chunk->getSourceName()));
}
$store = new MemoryVectorStore(topK: 4);
$store->addDocuments($embeddings->embedDocuments($chunks));
$retrieval = new SimilarityRetrieval($store, $embeddings);
$questions = require __DIR__ . '/fixtures/quality/questions.php';
if (count(array_filter(array_keys($questions), static fn (string $l): bool
    => str_ends_with($l, '(weak)'))) !== 2) {
    throw new RuntimeException('the plan and hours questions are labeled weak');
}
$result = new RetrievalEval($questions)->run($retrieval);
if ($result['total'] !== 5 || $result['hits'] < 4) {
    throw new RuntimeException('eval: ' . implode(' | ', $result['rows']));
}
$found = $retrieval->retrieve(new UserMessage('Can Tidepool export reports to Excel?'));
$kept = new FixedThresholdPostProcessor(0.50)
    ->process(new UserMessage('x'), $found);
if ($found === [] || $kept !== []) {
    throw new RuntimeException('fixed threshold did not refuse: ' . count($kept));
}
$good = $retrieval->retrieve(new UserMessage('Where do we set the retry limit?'));
$keptGood = new FixedThresholdPostProcessor(0.50)
    ->process(new UserMessage('x'), $good);
if ($keptGood === [] || $keptGood[0]->getSourceName() !== 'setup.md'
    || ($keptGood[0]->getMetadata()['heading_path'] ?? '') !== 'Tidepool setup guide > Retry limit') {
    throw new RuntimeException('fixed threshold dropped a good hit');
}
$adaptive = new AdaptiveThresholdPostProcessor()
    ->process(new UserMessage('x'), $good);
if ($adaptive === [] || count($adaptive) > count($good)) {
    throw new RuntimeException('adaptive threshold misbehaved');
}
// Segments 3c and 3e: with four scores the adaptive line always drops the lowest one; with the live scores
// read in the narration (0.56 0.49 0.48 0.44 and 0.59 0.30 0.24 0.13) it keeps three of four.
foreach ([[0.56, 0.49, 0.48, 0.44], [0.59, 0.30, 0.24, 0.13]] as $scores) {
    $docsWithScores = array_map(static fn (float $s): NeuronAI\RAG\Document
        => new NeuronAI\RAG\Document('x')->setScore($s), $scores);
    $count = count(new AdaptiveThresholdPostProcessor()
        ->process(new UserMessage('x'), $docsWithScores));
    $fixed = count(new FixedThresholdPostProcessor(0.50)
        ->process(new UserMessage('x'), $docsWithScores));
    if ($count !== 3 || $fixed !== 1) {
        throw new RuntimeException("narrated counts: adaptive {$count}, fixed {$fixed}");
    }
}
echo 'RETRIEVAL QUALITY OK' . PHP_EOL;
