<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\SourceExamples;
use Dotenv\Dotenv;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
$root = dirname(__DIR__);
Dotenv::createImmutable($root)->load();
$path = $root . '/fixtures/sources/invoice.pdf';
$link = new ImageContent(
    SourceExamples::PUBLIC_IMAGE,
    SourceType::URL, 'image/png',
);
$inline = new FileContent(
    SourceExamples::encoded($path),
    SourceType::BASE64, 'application/pdf', 'invoice.pdf',
);
$reference = new FileContent(
    'PLACEHOLDER_NOT_AN_UPLOAD',
    SourceType::ID, 'application/pdf', 'invoice.pdf',
);
if (($argv[1] ?? '') !== '--live') {
    SourceExamples::summary([$link, $inline, $reference]);
    echo 'Representations inspected; placeholder never sent.' . PHP_EOL;
    exit;
}
$reference = new FileContent(
    SourceExamples::uploadedId($root . '/storage/source-upload.json'),
    SourceType::ID, 'application/pdf', 'invoice.pdf',
);
SourceExamples::summary([$link, $inline, $reference]);
SourceExamples::ask($inline, 'MAPLE-731');
SourceExamples::ask($reference, 'MAPLE-731');
echo 'Source comparison ' . 'complete.' . PHP_EOL;
