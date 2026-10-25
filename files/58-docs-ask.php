<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Docs\DocsAgent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\UniqueIdGenerator;
$rag = require __DIR__ . '/rag-bootstrap.php';
$questions = require dirname(__DIR__) . '/fixtures/docs-questions.php';
$input = trim($argv[1] ?? '');
$question = $questions[$input][0] ?? $input;
if ($question === '') {
    fwrite(STDERR, "Give a question or a label.\n");
    exit(1);
}
$agent = DocsAgent::make($rag->provider, $rag->embeddings,
    $rag->stores->create('docs'))
    ->setThreadId(UniqueIdGenerator::generateId('docs_'));
$reply = $agent->chat(new UserMessage($question))->getMessage();
echo $reply?->getContent(), "\n";
echo "Answer complete: {$input}\n";
