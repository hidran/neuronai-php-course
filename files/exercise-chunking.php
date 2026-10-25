<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Splitter\AbstractSplitter;
use NeuronAI\RAG\Splitter\DelimiterTextSplitter;
use NeuronAI\RAG\Splitter\SentenceTextSplitter;
use NeuronAI\RAG\Splitter\SplitterInterface;
// Reference solution: one chunk per question and answer pair.
final class FaqSplitter extends AbstractSplitter
{
    #[\Override]
    public function splitDocument(Document $document): array
    {
        $chunks = [];
        $entries = preg_split('/^(?=Q: )/m', $document->getContent());
        foreach ($entries as $entry) {
            $entry = trim($entry);
            if (!str_starts_with($entry, 'Q: ')) {
                continue; // the page title is not an answer
            }
            $chunks[] = (new Document($entry))
                ->setSourceType($document->getSourceType())
                ->setSourceName($document->getSourceName())
                ->setMetadata($document->getMetadata())
                ->addMetadata('question', strtok($entry, "\n"));
        }
        return $chunks;
    }
}
$faq = dirname(__DIR__) . '/fixtures/rag-chunks/faq.md';
preg_match_all('/^Q: .+\nA: .+$/m', (string) file_get_contents($faq), $pairs);
$pairs = $pairs[0];
/** @var array<string, SplitterInterface> $configs */
$configs = [
    'delimiter' => new DelimiterTextSplitter(maxLength: 80, separator: '.'),
    'sentence' => new SentenceTextSplitter(maxWords: 20, minWords: 5),
    'faq' => new FaqSplitter(),
];
$results = [];
foreach ($configs as $name => $splitter) {
    $chunks = FileDataLoader::for($faq)->withSplitter($splitter)->getDocuments();
    $texts = array_map(static fn (Document $c): string => $c->getContent(), $chunks);
    // A pair is intact when one chunk holds the whole question and answer.
    $intact = count(array_filter(
        $pairs,
        static fn (string $pair): bool => array_any(
            $texts,
            static fn (string $text): bool => str_contains(
                preg_replace('/\s+/', ' ', $text),
                preg_replace('/\s+/', ' ', $pair),
            ),
        ),
    ));
    $results[$name] = [$chunks, $intact];
    printf(
        "%-9s %d chunks, largest %d chars / %d words, intact pairs %d/%d\n",
        $name,
        count($chunks),
        max(array_map('mb_strlen', $texts)),
        max(array_map('str_word_count', $texts)),
        $intact,
        count($pairs),
    );
}
[$faqChunks, $faqIntact] = $results['faq'];
$checks = [
    'the FAQ has four pairs' => count($pairs) === 4,
    'faq splitter keeps every pair intact' => $faqIntact === count($pairs),
    'delimiter splitter breaks at least one pair' => $results['delimiter'][1] < count($pairs),
    'every faq chunk keeps its source and names its question' => array_all(
        $faqChunks,
        static fn (Document $c): bool => $c->getSourceType() === 'files'
            && $c->getSourceName() === $faq
            && str_starts_with($c->getMetadata()['question'] ?? '', 'Q: '),
    ),
    'an empty page gives no chunks' => (new FaqSplitter())->splitDocument(new Document('')) === [],
];
foreach ($checks as $label => $ok) {
    printf("%s %s\n", $ok ? 'ok  ' : 'FAIL', $label);
    if (!$ok) {
        exit(1);
    }
}
printf("First faq chunk question: %s\n", $faqChunks[0]->getMetadata()['question']);
echo "Chunking exercise complete.\n";
