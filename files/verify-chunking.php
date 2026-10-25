<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/HeadingSplitter.php';
require __DIR__ . '/ChunkComparison.php';
require __DIR__ . '/../../11-03-vector-stores-and-retrieval/files/KeywordEmbeddings.php';
use App\Rag\ChunkComparison;
use App\Rag\HeadingSplitter;
use App\Rag\KeywordEmbeddings;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Splitter\DelimiterTextSplitter;
use NeuronAI\RAG\Splitter\SentenceTextSplitter;
$path = __DIR__ . '/fixtures/rag-chunks/returns-guide.md';
$source = new Document((string) file_get_contents($path))
    ->setSourceType('guide')->setSourceName('returns-guide.md')
    ->addMetadata('language', 'en');
$check = static function (bool $ok, string $label): void {
    if (!$ok) {
        throw new RuntimeException("FAILED: {$label}");
    }
};
$custom = new HeadingSplitter()->splitDocument($source);
$check(count($custom) === 4, 'four sections (intro plus three)');
$check($custom[1]->getMetadata()['heading_path'] === 'Returns guide > Refunds',
    'heading path recorded in metadata');
$check($custom[1]->getMetadata()['language'] === 'en', 'metadata kept');
$check(str_starts_with($custom[1]->getContent(), '## Refunds'),
    'heading text kept');
$check(new HeadingSplitter()->splitDocument(new Document('')) === [],
    'empty string gives no chunks');
$long = new Document("## Notes\n\n" . str_repeat('Every parcel is checked. ', 10));
$pieces = new HeadingSplitter(maxLength: 60)->splitDocument($long);
$check(count($pieces) > 1 && max(array_map(
    static fn (Document $c): int => mb_strlen($c->getContent()), $pieces)) <= 60,
    'long section cut');
$check(array_all($pieces, static fn (Document $c): bool
    => $c->getMetadata()['heading_path'] === 'Notes'), 'pieces keep the path');
$builtIn = [...new DelimiterTextSplitter(maxLength: 120, separator: '.')
    ->splitDocument($source),
    ...new SentenceTextSplitter(maxWords: 20)->splitDocument($source)];
$check($builtIn !== [] && array_all($builtIn, static fn (Document $c): bool
    => $c->getSourceName() === 'returns-guide.md'), 'built-in keep source');
ob_start();
new ChunkComparison(new KeywordEmbeddings(), $path, [
    'Does my store credit expire?' => ['Store credit', ['never expires']],
])->indexAndRetrieve('sections', new HeadingSplitter());
$output = ob_get_clean();
$check(str_contains($output, 'found Returns guide > Store credit, complete'),
    "comparison output: {$output}");
echo 'CHUNKING OK' . PHP_EOL;
