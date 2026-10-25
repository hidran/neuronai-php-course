<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\DataLoader\HtmlReader;
use NeuronAI\RAG\DataLoader\PdfReader;
use NeuronAI\RAG\Splitter\SentenceTextSplitter;
$docs = dirname(__DIR__) . '/fixtures/rag-docs';
$loader = FileDataLoader::for($docs)
    ->addReader('html', new HtmlReader())
    ->addReader('pdf', new PdfReader());
$chunks = $loader->getDocuments();
$total = 0;
foreach ($chunks as $chunk) {
    $text = trim($chunk->getContent());
    if ($text === '') {
        throw new RuntimeException('An empty chunk reached the index.');
    }
    $total += strlen($text);
    printf("%-20s %4d chars | %s\n",
        basename($chunk->getSourceName()), strlen($text), substr($text, 0, 50));
}
printf("Chunks: %d, characters: %d\n", count($chunks), $total);
$policy = FileDataLoader::for($docs . '/return-policy.md');
$policy->withSplitter(new SentenceTextSplitter(maxWords: 25));
foreach ($policy->getDocuments() as $chunk) {
    $text = trim($chunk->getContent());
    $words = str_word_count($text);
    printf("sentence chunk: %2d words | %s\n", $words, substr($text, 0, 58));
    if ($words > 25) {
        throw new RuntimeException('A chunk exceeded the word limit.');
    }
}
echo "Loader exercise complete.\n";
