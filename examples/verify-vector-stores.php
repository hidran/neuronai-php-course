<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/KeywordEmbeddings.php';
require __DIR__ . '/VectorStoreSettings.php';
require __DIR__ . '/VectorStoreFactory.php';
use App\Config\VectorStoreFactory;
use App\Config\VectorStoreSettings;
use App\Rag\KeywordEmbeddings;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\Filter\Filter;
use NeuronAI\RAG\VectorStore\Filter\FilterGroup;
use NeuronAI\RAG\VectorStore\SearchRequest;
function check(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException("FAILED: {$label}");
    }
}
$root = sys_get_temp_dir() . '/verify-vectors-' . bin2hex(random_bytes(4));
$embeddings = new KeywordEmbeddings();
$texts = [
    'support-hours.md' => 'Support answers chats within two hours.',
    'support-login.md' => 'Locked accounts reset at the sign in page.',
    'billing.md' => 'Invoices are emailed on the first of the month.',
];
$documents = [];
foreach ($texts as $source => $text) {
    $documents[] = new Document($text)->setSourceType('files')
        ->setSourceName($source);
}
$embedded = $embeddings->embedDocuments($documents);
$question = new SearchRequest(
    $embeddings->embedText('How fast does support answer a chat?'), topK: 2);
$names = static fn (iterable $docs): array => array_map(
    static fn (Document $d): string => $d->getSourceName(), [...$docs]);
foreach (['memory', 'file'] as $kind) {
    $store = new VectorStoreFactory(VectorStoreSettings::fromEnv(
        ['VECTOR_STORE' => $kind], $root))->create('support-demo');
    $store->addDocuments($embedded);
    $found = $names($store->search($question));
    check($found === ['support-hours.md', 'support-login.md']
        || $found[0] === 'support-hours.md', "{$kind}: support hours first");
    check(count($found) === 2, "{$kind}: top K two returns two");
    $scoped = new SearchRequest($question->embedding,
        filters: Filter::eq('sourceName', 'billing.md'), topK: 2);
    check(count([...$store->search($scoped)]) === 1, "{$kind}: filter narrows");
    $store->delete(FilterGroup::and(Filter::eq('sourceType', 'files'),
        Filter::eq('sourceName', 'support-hours.md')));
    $store->addDocuments($embeddings->embedDocuments([
        new Document('Support answers chats within one hour.')
            ->setSourceType('files')->setSourceName('support-hours.md')]));
    $all = [...$store->search(new SearchRequest($question->embedding, topK: 10))];
    check(count($all) === 3, "{$kind}: reindex keeps three documents");
    $top = array_first($all);
    check(str_contains($top->getContent(), 'one hour'), "{$kind}: new text wins");
}
check(is_file($root . '/storage/vectors/support-demo.store'), 'collection file');
array_map(unlink(...), glob($root . '/storage/vectors/*'));
rmdir($root . '/storage/vectors');
rmdir($root . '/storage');
rmdir($root);
echo 'VECTOR STORES OK' . PHP_EOL;
