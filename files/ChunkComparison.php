<?php
declare(strict_types=1);
namespace App\Rag;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Splitter\SplitterInterface;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
use NeuronAI\RAG\VectorStore\SearchRequest;
/**
 * Indexes one guide with one splitter in its own in-memory index and
 * checks the top passage of each labeled question: which section it
 * came from and which needed facts it carries.
 */
final class ChunkComparison
{
    /** @var array<string, list<float>> */
    private array $questionVectors = [];
    /**
     * @param array<string, array{string, list<string>}> $questions
     *        question => [expected section, facts a full answer needs]
     */
    public function __construct(
        private readonly EmbeddingsProviderInterface $embeddings,
        private readonly string $guide,
        private readonly array $questions,
    ) {
    }
    public function indexAndRetrieve(string $name,
        SplitterInterface $splitter): void
    {
        $chunks = FileDataLoader::for($this->guide)
            ->withSplitter($splitter)->getDocuments();
        $index = new MemoryVectorStore(topK: 1);
        $index->addDocuments(
            $this->embeddings->embedDocuments($chunks));
        printf("%s: %d chunks indexed\n", $name, count($chunks));
        foreach ($this->questions as $question => [$section, $facts]) {
            $this->questionVectors[$question] ??= $this->embeddings
                ->embedText($question);
            $top = array_first([...$index->search(
                new SearchRequest($this->questionVectors[$question]))]);
            $passage = trim($top->getContent());
            $missing = array_values(array_filter($facts,
                static fn (string $fact): bool
                    => stripos($passage, $fact) === false));
            printf("  expected %s, found %s, %s (%d chars)\n", $section,
                $top->getMetadata()['heading_path']
                    ?? $this->sectionOf($passage),
                $missing === [] ? 'complete'
                    : 'missing: ' . implode(', ', $missing),
                mb_strlen($passage));
        }
    }
    /** The heading the passage starts under, read from the guide. */
    private function sectionOf(string $passage): string
    {
        $text = (string) file_get_contents($this->guide);
        $start = strpos($text, $passage);
        if ($start === false) {
            return 'unknown';
        }
        preg_match_all('/^## (.+)$/m', $text, $headings,
            PREG_OFFSET_CAPTURE);
        $found = 'introduction';
        foreach ($headings[1] as [$title, $offset]) {
            if ($offset - 3 <= $start) {
                $found = $title;
            }
        }
        return $found;
    }
}
