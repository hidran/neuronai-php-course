<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
use App\Config\EmbeddingsFactory;
use App\Config\EmbeddingsSettings;
use NeuronAI\RAG\VectorSimilarity;
$embeddings = EmbeddingsSettings::fromEnv($_ENV);
$provider = new EmbeddingsFactory($embeddings)->create();
echo "Embeddings: {$embeddings->provider->value}, {$embeddings->model}"
    . PHP_EOL;
$texts = [
    'package' => 'A customer can return a damaged package.',
    'broken' => 'We accept shipments that arrive broken.',
    'revenue' => 'Our quarterly revenue increased.',
];
$vectors = [];
foreach ($texts as $label => $text) {
    $vectors[$label] = $provider->embedText($text);
    printf("%s: %d dimensions; preview %s\n", $label,
        count($vectors[$label]),
        json_encode(array_slice($vectors[$label], 0, 3)));
}
$related = VectorSimilarity::cosineSimilarity(
    $vectors['package'], $vectors['broken']);
$other = VectorSimilarity::cosineSimilarity(
    $vectors['package'], $vectors['revenue']);
printf("Related pair similarity: %.6f\n", $related);
printf("Other topic: %.6f\n", $other);
echo "Pair comparisons complete.\n";
$self = VectorSimilarity::cosineSimilarity(
    $vectors['package'], $vectors['package']);
printf("Self similarity: %.9f\n", $self);
$distance = VectorSimilarity::cosineDistance(
    $vectors['package'], $vectors['broken']);
printf("Related pair distance: %.6f\n", $distance);
$converted = VectorSimilarity::similarityFromDistance($distance);
printf("Converted similarity: %.6f\n", $converted);
printf("Conversion agrees: %s\n",
    abs($converted - $related) < 1e-9 ? 'yes' : 'no');
echo "Embedding experiment complete.\n";
