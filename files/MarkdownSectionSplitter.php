<?php
declare(strict_types=1);
namespace App\Docs;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Splitter\AbstractSplitter;
/**
 * One chunk per section. A long section is cut between paragraphs,
 * a long paragraph between words; every chunk keeps its provenance.
 */
final class MarkdownSectionSplitter extends AbstractSplitter
{
    public function __construct(private readonly int $maxChars = 600)
    {
    }
    #[\Override]
    public function splitDocument(Document $document): array
    {
        $chunks = [];
        foreach (preg_split('/^(?=## )/m', $document->getContent())
            as $section) {
            foreach ($this->pieces(trim($section)) as $text) {
                $chunks[] = new Document($text)
                    ->setSourceType($document->getSourceType())
                    ->setSourceName($document->getSourceName())
                    ->setMetadata($document->getMetadata());
            }
        }
        return $chunks;
    }
    /** @return list<string> */
    public function pieces(string $section): array
    {
        if ($section === '') {
            return [];
        }
        if (mb_strlen($section) <= $this->maxChars) {
            return [$section];
        }
        $pieces = [];
        $current = '';
        foreach (preg_split('/\n{2,}/', $section) as $paragraph) {
            foreach ($this->wrap($paragraph) as $part) {
                $joined = $current === '' ? $part
                    : "{$current}\n\n{$part}";
                if ($current !== ''
                    && mb_strlen($joined) > $this->maxChars) {
                    $pieces[] = $current;
                    $joined = $part;
                }
                $current = $joined;
            }
        }
        $pieces[] = $current;
        return $pieces;
    }
    /** @return list<string> Words are kept whole; multibyte safe. */
    private function wrap(string $paragraph): array
    {
        $parts = [];
        $line = '';
        foreach (preg_split('/\s+/u', trim($paragraph)) as $word) {
            $candidate = $line === '' ? $word : "{$line} {$word}";
            if ($line !== ''
                && mb_strlen($candidate) > $this->maxChars) {
                $parts[] = $line;
                $candidate = $word;
            }
            $line = $candidate;
        }
        return $line === '' ? [] : [...$parts, $line];
    }
}
