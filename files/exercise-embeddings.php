<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
use App\Config\EmbeddingsFactory;
use App\Config\EmbeddingsSettings;
use NeuronAI\RAG\VectorSimilarity;
$provider = new EmbeddingsFactory(EmbeddingsSettings::fromEnv($_ENV))
    ->create();
$query = 'Can I return a damaged package?';
$passages = [
    'allowed' => 'Customers may return damaged packages.',
    'denied' => 'Customers may not return damaged packages.',
    'revenue' => 'Our quarterly revenue increased.',
];
$queryVector = $provider->embedText($query);
$ranking = [];
foreach ($passages as $id => $text) {
    $ranking[$id] = VectorSimilarity::cosineSimilarity(
        $queryVector, $provider->embedText($text));
}
arsort($ranking, SORT_NUMERIC);
foreach ($ranking as $id => $score) {
    printf("%s: %.6f | %s\n", $id, $score, $passages[$id]);
}
echo "Read each passage before answering the question.\n";
echo "Negation experiment complete.\n";
