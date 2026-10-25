<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Rag\HeadingSplitter;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Splitter\SentenceTextSplitter;
$guide = dirname(__DIR__) . '/fixtures/rag-chunks/returns-guide.md';
$text = 'Returns are handled by the Cedar support team. '
    . 'Customers may request a refund within seventeen days. '
    . 'After that period, only store credit is available. '
    . 'Damaged parcels need photographs from the customer. '
    . 'A returned item must be unused, complete with its original '
    . 'packaging, the receipt, and every accessory in the box, '
    . 'unless the parcel arrived damaged.'
    . "\n\nShipping. Orders leave the warehouse in two business days. "
    . 'Tracking numbers are sent by email.';
$document = new Document($text)
    ->setSourceType('guide')
    ->setSourceName('returns.txt')
    ->addMetadata('language', 'en');
$splitter = new SentenceTextSplitter(maxWords: 25, overlapWords: 0,
    minWords: 8);
$chunks = $splitter->splitDocuments([$document]);
$total = 0;
foreach ($chunks as $chunk) {
    $part = trim($chunk->getContent());
    $total += mb_strlen($part);
    printf("%3d chars, %2d words | %s\n", mb_strlen($part),
        str_word_count($part), $part);
}
printf("Chunks: %d, characters: %d\n", count($chunks), $total);
$overlap = new SentenceTextSplitter(maxWords: 25, overlapWords: 5,
    minWords: 8)->splitDocuments([$document]);
printf("With overlap: %d chunks, second starts with: %s\n",
    count($overlap), mb_substr(trim($overlap[1]->getContent()), 0, 40));
$sections = FileDataLoader::for($guide)
    ->withSplitter(new HeadingSplitter(maxLength: 400))->getDocuments();
foreach ($sections as $section) {
    printf("Section %s: %d chars\n",
        $section->getMetadata()['heading_path'],
        mb_strlen($section->getContent()));
}
echo "Chunking complete.\n";
