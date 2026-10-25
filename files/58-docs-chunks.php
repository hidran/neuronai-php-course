<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Docs\MarkdownSectionSplitter;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\Document;
$splitter = new MarkdownSectionSplitter();
$short = new Document(
    "# Short\n\nIntro.\n\n## One\n\nA.\n\n## Two\n\nB.");
printf("Short guide: %d chunks\n",
    count($splitter->splitDocument($short)));
printf("Empty document: %d chunks\n",
    count($splitter->splitDocument(new Document(''))));
$text = str_repeat('Una frase lunga sulle regole, con accenti è. ', 12);
$long = new Document("## Long\n\n{$text}\n\n{$text}\n\n{$text}{$text}")
    ->setSourceType('files')->setSourceName('long.md')
    ->setMetadata(['team' => 'docs']);
$pieces = $splitter->splitDocument($long);
$longest = max(array_map(
    static fn (Document $p): int => mb_strlen($p->getContent()),
    $pieces));
$kept = array_all($pieces, static fn (Document $p): bool
    => $p->getSourceName() === 'long.md'
    && $p->getMetadata() === ['team' => 'docs']);
printf("Long section: %d pieces, longest %d chars, provenance: %s\n",
    count($pieces), $longest, $kept ? 'yes' : 'no');
$docs = FileDataLoader::for(dirname(__DIR__) . '/fixtures/docs')
    ->withSplitter($splitter)->getDocuments();
usort($docs, static fn (Document $a, Document $b): int
    => strcmp($a->getSourceName(), $b->getSourceName()));
foreach ($docs as $i => $doc) {
    printf("%2d %-10s %s\n", $i + 1, basename($doc->getSourceName()),
        strtok($doc->getContent(), "\n"));
}
printf("Corpus chunks: %d\n", count($docs));
echo "Chunk inspection complete.\n";
