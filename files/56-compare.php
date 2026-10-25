<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
use App\Config\EmbeddingsFactory;
use App\Config\EmbeddingsSettings;
use App\Rag\ChunkComparison;
use App\Rag\HeadingSplitter;
use NeuronAI\RAG\Splitter\DelimiterTextSplitter;
$guide = dirname(__DIR__) . '/fixtures/rag-chunks/returns-guide.md';
$embeddings = new EmbeddingsFactory(EmbeddingsSettings::fromEnv($_ENV))
    ->create();
// question => [section that should answer it, facts it needs]
$questions = [
    'Can I get a refund three weeks after delivery?'
        => ['Refunds', ['seventeen days', 'store credit']],
    'Does my store credit expire?'
        => ['Store credit', ['never expires']],
    'When will my order leave the warehouse?'
        => ['Shipping', ['two business days']],
];
$configs = [
    'sentences' => new DelimiterTextSplitter(maxLength: 80,
        separator: '.'),
    'sections' => new HeadingSplitter(maxLength: 400),
];
$comparison = new ChunkComparison($embeddings, $guide, $questions);
foreach ($configs as $name => $splitter) {
    $comparison->indexAndRetrieve($name, $splitter);
}
echo "Comparison complete.\n";
