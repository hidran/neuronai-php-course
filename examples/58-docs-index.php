<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Docs\DocsAgent;
use App\Docs\DocsSync;
use App\Docs\MarkdownSectionSplitter;
$rag = require __DIR__ . '/rag-bootstrap.php';
$root = dirname(__DIR__);
$start = microtime(true);
$store = $rag->stores->create('docs');
$agent = DocsAgent::make($rag->provider, $rag->embeddings, $store);
$sync = new DocsSync($agent, $store, new MarkdownSectionSplitter(),
    $root . '/storage/docs-manifest.json');
$documents = $sync->load($root . '/fixtures/docs');
printf("Chunks: %d\n", count($documents));
$gone = $sync->sync($root . '/fixtures/docs');
printf("Sources: %s\n", implode(', ', $sync->indexedSources()));
printf("Removed: %s\n", $gone === [] ? 'none' : implode(', ', $gone));
printf("Indexed successfully in %.1f s\n", microtime(true) - $start);
