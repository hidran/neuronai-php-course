<?php
declare(strict_types=1);
namespace App\Docs;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\Splitter\SplitterInterface;
use NeuronAI\RAG\VectorStore\Filter\Filter;
use NeuronAI\RAG\VectorStore\Filter\FilterGroup;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use RuntimeException;
/**
 * Keeps an index equal to a folder: changed sources are reindexed,
 * removed or renamed sources are deleted. The manifest remembers what
 * was indexed, so no store enumeration is needed.
 */
final readonly class DocsSync
{
    public function __construct(
        private RAG $agent,
        private VectorStoreInterface $store,
        private SplitterInterface $splitter,
        private string $manifest,
    ) {
    }
    /** @return list<Document> chunks with relative source names */
    public function load(string $root): array
    {
        $real = realpath($root)
            ?: throw new RuntimeException("No docs folder {$root}");
        $documents = FileDataLoader::for($real)
            ->withSplitter($this->splitter)->getDocuments();
        foreach ($documents as $document) {
            $document->setSourceName(
                substr($document->getSourceName(), strlen($real) + 1));
        }
        return $documents;
    }
    /** @return list<string> the sources removed from the index */
    public function sync(string $root): array
    {
        $documents = $this->load($root);
        $current = array_values(array_unique(array_map(
            static fn (Document $d): string => $d->getSourceName(),
            $documents)));
        sort($current);
        $previous = is_file($this->manifest) ? json_decode(
            (string) file_get_contents($this->manifest), true) : [];
        $this->agent->reindexBySource($documents);
        $gone = array_values(array_diff($previous, $current));
        foreach ($gone as $name) {
            $this->store->delete(FilterGroup::and(
                Filter::eq('sourceType', 'files'),
                Filter::eq('sourceName', $name),
            ));
        }
        file_put_contents($this->manifest, json_encode($current));
        return $gone;
    }
    /** @return list<string> */
    public function indexedSources(): array
    {
        return is_file($this->manifest) ? json_decode(
            (string) file_get_contents($this->manifest), true) : [];
    }
}
