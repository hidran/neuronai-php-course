<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/VectorStoreSettings.php';
require __DIR__ . '/VectorStoreFactory.php';
use App\Config\VectorStoreFactory;
use App\Config\VectorStoreSettings;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
use NeuronAI\RAG\VectorStore\SearchRequest;
$root = sys_get_temp_dir() . '/vs-' . bin2hex(random_bytes(4));
$memory = new VectorStoreFactory(VectorStoreSettings::fromEnv([], $root))
    ->create('demo');
if (!$memory instanceof MemoryVectorStore) {
    throw new RuntimeException('default must be the memory store');
}
$settings = VectorStoreSettings::fromEnv(['VECTOR_STORE' => 'file',
    'VECTOR_STORE_PATH' => 'storage/vectors', 'VECTOR_STORE_TOP_K' => '2'],
    $root);
if ($settings->topK !== 2 || $settings->directory !== $root . '/storage/vectors') {
    throw new RuntimeException('settings not read');
}
$file = new VectorStoreFactory($settings)->create('demo');
if (!$file instanceof FileVectorStore) {
    throw new RuntimeException('file store expected');
}
$file->addDocuments([new Document('a')->setEmbedding([1.0, 0.0]),
    new Document('b')->setEmbedding([0.0, 1.0]),
    new Document('c')->setEmbedding([0.9, 0.1])]);
$hits = [...$file->search(new SearchRequest([1.0, 0.0]))];
if (count($hits) !== 2 || $hits[0]->getContent() !== 'a') {
    throw new RuntimeException('search through the factory store failed');
}
if (!is_file($root . '/storage/vectors/demo.store')) {
    throw new RuntimeException('collection file not created');
}
try {
    VectorStoreSettings::fromEnv(['VECTOR_STORE' => 'pinecone'], $root);
    throw new LogicException('unknown store accepted');
} catch (RuntimeException) {
}
array_map(unlink(...), glob($root . '/storage/vectors/*'));
rmdir($root . '/storage/vectors');
rmdir($root . '/storage');
rmdir($root);
echo 'STORE CONFIG OK' . PHP_EOL;
