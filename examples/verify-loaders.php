<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/MainContentReader.php';
use App\Rag\MainContentReader;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\DataLoader\HtmlReader;
use NeuronAI\RAG\DataLoader\PdfReader;
use NeuronAI\RAG\Splitter\SentenceTextSplitter;
$docs = __DIR__ . '/fixtures/rag-docs';
// PdfReader needs the pdftotext binary; without it the PDF is read raw.
$pdfTool = trim((string) shell_exec('command -v pdftotext')) !== '';
$loader = FileDataLoader::for($docs)->addReader('html', new HtmlReader());
if ($pdfTool) {
    $loader->addReader('pdf', new PdfReader());
}
$chunks = $loader->getDocuments();
$sources = [];
foreach ($chunks as $chunk) {
    if (trim($chunk->getContent()) === '' || $chunk->getSourceType() !== 'files') {
        throw new RuntimeException('empty chunk or wrong source type');
    }
    $sources[basename($chunk->getSourceName())] = true;
}
$found = array_keys($sources);
sort($found);
if ($found !== ['return-policy.md', 'support-page.html',
    'troubleshooting.txt', 'warranty.pdf']) {
    throw new RuntimeException('sources: ' . implode(',', $found));
}
$page = $docs . '/support-page.html';
$raw = FileDataLoader::for($page)->getDocuments()[0]->getContent();
$html = FileDataLoader::for($page)->addReader('html', new HtmlReader())
    ->getDocuments()[0]->getContent();
$main = FileDataLoader::for($page)
    ->addReader('html', new MainContentReader())
    ->getDocuments()[0]->getContent();
if (!str_contains($raw, '<h1>') || str_contains($html, '<h1>')
    || str_contains($main, '<')) {
    throw new RuntimeException('readers: markup handling wrong');
}
if (!str_contains($html, 'warranty') || !str_contains($main, 'warranty')) {
    throw new RuntimeException('readers lost the article text');
}
foreach (['nav', 'footer'] as $tag) {
    if (preg_match("/<{$tag}\\b/i", $raw) === 1
        && mb_strlen($main) >= mb_strlen($html)) {
        throw new RuntimeException('main content reader removed nothing');
    }
}
if ($pdfTool) {
    $pdf = FileDataLoader::for($docs . '/warranty.pdf')
        ->addReader('pdf', new PdfReader())->getDocuments();
    if (!str_contains($pdf[0]->getContent(), 'two-year limited warranty')) {
        throw new RuntimeException('pdf text layer not extracted');
    }
} else {
    echo 'pdftotext missing: PDF text check skipped' . PHP_EOL;
}
$policy = FileDataLoader::for($docs . '/return-policy.md')
    ->withSplitter(new SentenceTextSplitter(maxWords: 25))->getDocuments();
foreach ($policy as $chunk) {
    if (str_word_count($chunk->getContent()) > 25) {
        throw new RuntimeException('a chunk exceeded the word limit');
    }
}
echo 'LOADERS OK' . PHP_EOL;
