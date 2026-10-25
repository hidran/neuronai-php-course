<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
use NeuronAI\RAG\Document;
use App\Config\EmbeddingsFactory;
use App\Config\EmbeddingsSettings;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\Filter\Filter;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
use NeuronAI\RAG\VectorStore\SearchRequest;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
// Exercise reference: scoped search, a top K maximum, stored fields and
// the replacement of one source, on a dedicated file store.
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("Check failed: {$message}");
    }
    echo "ok: {$message}\n";
}
/** @return Document[] */
function results(VectorStoreInterface $store, SearchRequest $request): array
{
    $found = [];
    foreach ($store->search($request) as $document) {
        $found[] = $document;
    }
    return $found;
}
$embeddings = new EmbeddingsFactory(EmbeddingsSettings::fromEnv($_ENV))
    ->create();
$directory = dirname(__DIR__) . '/storage/vectors-exercise';
$path = $directory . '/catalog.store';
if (is_file($path)) {
    unlink($path);
}
$file = new FileVectorStore(directory: $directory, topK: 3,
    name: 'catalog', ext: '.store');
check(!is_file($path), 'constructing the file store creates no file');
$corpus = [
    ['faq', 'refunds.md',
        'Refunds go back to the original card within five days.'],
    ['faq', 'shipping.md',
        'Orders leave the Lyon warehouse every weekday morning.'],
    ['changelog', 'release-2.md',
        'Release two lets customers choose store credit instead.'],
    ['changelog', 'release-1.md',
        'Release one added email alerts when a parcel ships.'],
];
$documents = [];
foreach ($corpus as [$type, $name, $text]) {
    $documents[] = (new Document($text))
        ->setSourceType($type)->setSourceName($name);
}
$embedded = $embeddings->embedDocuments($documents);
$memory = new MemoryVectorStore(topK: 3);
$memory->addDocuments($embedded);
$file->addDocuments($embedded);
check(is_file($path), 'the first write creates the store file');
// 1. Same documents, same request: both backends agree on the ranking.
$money = new SearchRequest(
    embedding: $embeddings->embedText('How do I get my money back?'),
    topK: 2,
);
$fromMemory = array_map(fn ($d) => $d->getSourceName(),
    results($memory, $money));
$fromFile = array_map(fn ($d) => $d->getSourceName(),
    results($file, $money));
printf("Memory ranking: %s\n", implode(', ', $fromMemory));
printf("File ranking:   %s\n", implode(', ', $fromFile));
check($fromMemory === $fromFile, 'memory and file stores rank alike');
check($fromFile[0] === 'refunds.md', 'the refund passage ranks first');
// 2. A filter narrows the candidates; top K is only a maximum.
$scoped = new SearchRequest(
    embedding: $embeddings->embedText('What changed for refunds?'),
    filters: Filter::eq('sourceType', 'changelog'),
    topK: 5,
);
$changes = results($file, $scoped);
foreach ($changes as $document) {
    printf("changelog | %s | %.3f\n", $document->getSourceName(),
        $document->getScore());
}
check(count($changes) === 2, 'top K 5 with a filter returns 2 documents');
check(array_all($changes,
    fn ($d) => $d->getSourceType() === 'changelog'),
    'every result matches the filter');
// 3. The score belongs to the query, not to the stored row.
$row = json_decode((string) fgets(fopen($path, 'r')), true);
printf("Stored fields: %s\n", implode(', ', array_keys($row)));
check(!array_key_exists('score', $row), 'no score is stored');
$refundScore = fn (SearchRequest $r) => array_values(array_filter(
    results($memory, new SearchRequest($r->embedding, topK: 4)),
    fn ($d) => $d->getSourceName() === 'refunds.md'))[0]->getScore();
printf("refunds.md scores: %.3f and %.3f\n",
    $refundScore($money), $refundScore($scoped));
check(abs($refundScore($money) - $refundScore($scoped)) > 0.001,
    'the same passage scores differently for another question');
// 4. A changed source: delete its old chunks, then add the new version.
$file->delete(Filter::eq('sourceName', 'refunds.md'));
$file->addDocument($embeddings->embedDocument(
    (new Document('Refunds now reach the original card in two days.'))
        ->setSourceType('faq')->setSourceName('refunds.md')));
$after = results($file, new SearchRequest(
    embedding: $money->embedding,
    filters: Filter::eq('sourceName', 'refunds.md'),
    topK: 3,
));
check(count($after) === 1, 'one refunds.md row after the replacement');
check(str_contains($after[0]->getContent(), 'two days'),
    'the new refund wording is the one returned');
echo "Vector store exercise complete.\n";
