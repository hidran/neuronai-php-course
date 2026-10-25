<?php
declare(strict_types=1);
namespace App\Rag;
use DOMDocument;
use DOMXPath;
use NeuronAI\Exceptions\DataReaderException;
use NeuronAI\RAG\DataLoader\ReaderInterface;
/** Reads an HTML page as text, without its navigation and footer. */
final readonly class MainContentReader implements ReaderInterface
{
    private const array BOILERPLATE = ['nav', 'footer', 'header',
        'script', 'style', 'aside'];
    #[\Override]
    public function read(string $filePath): string
    {
        $html = file_get_contents($filePath);
        if ($html === false) {
            throw new DataReaderException("Cannot read {$filePath}");
        }
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);
        foreach (self::BOILERPLATE as $tag) {
            $nodes = iterator_to_array($xpath->query("//{$tag}"));
            foreach ($nodes as $node) {
                $node->parentNode?->removeChild($node);
            }
        }
        $main = $xpath->query('//main')->item(0)
            ?? $dom->documentElement;
        $text = preg_replace('/[ \t]+/', ' ',
            $main?->textContent ?? '');
        return trim(preg_replace('/\n\s*\n+/', "\n\n", (string) $text));
    }
}
