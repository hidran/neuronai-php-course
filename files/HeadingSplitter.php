<?php
declare(strict_types=1);
namespace App\Rag;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Splitter\AbstractSplitter;
use NeuronAI\RAG\Splitter\DelimiterTextSplitter;
/**
 * One chunk per "## " section. Each chunk starts with its own heading
 * line and keeps the full heading path in metadata for citations.
 */
final class HeadingSplitter extends AbstractSplitter
{
    public function __construct(private readonly int $maxLength = 400)
    {
    }
    #[\Override]
    public function splitDocument(Document $document): array
    {
        $content = $document->getContent();
        $title = preg_match('/^# (.+)$/m', $content, $m)
            ? trim($m[1]) : '';
        $sections = preg_split('/^(?=## )/m', $content);
        $chunks = [];
        foreach (array_filter($sections, static fn (string $s): bool =>
            trim($s) !== '') as $section) {
            $heading = preg_match('/^## (.+)$/m', $section, $h)
                ? trim($h[1]) : 'Introduction';
            $path = $title === '' ? $heading : "{$title} > {$heading}";
            $piece = new Document(trim($section))
                ->setSourceType($document->getSourceType())
                ->setSourceName($document->getSourceName())
                ->setMetadata($document->getMetadata())
                ->addMetadata('heading_path', $path);
            foreach ($this->subdivide($piece) as $chunk) {
                $chunk->addMetadata('heading_path', $path);
                $chunks[] = $chunk;
            }
        }
        return $chunks;
    }
    /** @return list<Document> Oversized sections are cut on spaces. */
    private function subdivide(Document $section): array
    {
        return new DelimiterTextSplitter($this->maxLength, ' ')
            ->splitDocument($section);
    }
}
