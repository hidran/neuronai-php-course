<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Docs\DocsAgent;
use NeuronAI\Chat\Messages\UserMessage;
$rag = require __DIR__ . '/rag-bootstrap.php';
$questions = require dirname(__DIR__) . '/fixtures/docs-questions.php';
$input = trim($argv[1] ?? 'retry');
$question = $questions[$input][0] ?? $input;
echo "Question: {$question}\n";
$agent = DocsAgent::make($rag->provider, $rag->embeddings,
    $rag->stores->create('docs'));
$found = $agent->resolveRetrieval()
    ->retrieve(new UserMessage($question));
foreach ($found as $doc) {
    $text = preg_replace('/\s+/', ' ', $doc->getContent());
    printf("%.2f %-10s %.58s\n", $doc->getScore(),
        $doc->getSourceName(), $text);
}
$kept = array_first($agent->postProcessors())
    ->process(new UserMessage($question), $found);
printf("Above threshold: %d of %d\n", count($kept), count($found));
echo "Retrieval complete: {$input}\n";
