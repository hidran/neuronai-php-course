<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Rag\MainContentReader;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\DataLoader\HtmlReader;
use NeuronAI\RAG\DataLoader\PdfReader;
use NeuronAI\RAG\DataLoader\StringDataLoader;
$docs = dirname(__DIR__) . '/fixtures/rag-docs';
$chunks = FileDataLoader::for($docs)
    ->addReader('html', new HtmlReader())
    ->addReader('pdf', new PdfReader())
    ->getDocuments();
printf("Chunks loaded: %d\n", count($chunks));
foreach ($chunks as $index => $chunk) {
    printf("--- chunk %d\n", $index + 1);
    printf("type: %s | name: %s\n", $chunk->getSourceType(),
        basename($chunk->getSourceName()));
    printf("content: %.110s\n", trim($chunk->getContent()));
}
echo "Folder inspection complete.\n";
$page = $docs . '/support-page.html';
$raw = FileDataLoader::for($page)->getDocuments()[0];
printf("Raw markup kept: %s\n",
    str_contains($raw->getContent(), '<h1>') ? 'yes' : 'no');
$text = FileDataLoader::for($page)->addReader('html', new HtmlReader())
    ->getDocuments()[0];
printf("HtmlReader: %.110s\n", trim($text->getContent()));
$main = FileDataLoader::for($page)
    ->addReader('html', new MainContentReader())->getDocuments()[0];
printf("MainContentReader: %.110s\n", trim($main->getContent()));
echo "HTML inspection complete.\n";
$pdf = FileDataLoader::for($docs . '/warranty.pdf')
    ->addReader('pdf', new PdfReader())->getDocuments();
printf("PDF: %d chunks, text: %.40s\n", count($pdf),
    trim($pdf[0]->getContent()));
echo "PDF inspection complete.\n";
$article = StringDataLoader::for(
    'Cedar Support moved damage claims to the portal.')->getDocuments();
$article[0]->setSourceType('cms')->setSourceName('article-1042');
printf("String source: %s / %s\n", $article[0]->getSourceType(),
    $article[0]->getSourceName());
echo "Shared document shape confirmed.\n";
