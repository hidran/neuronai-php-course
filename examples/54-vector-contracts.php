<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use NeuronAI\Exceptions\VectorStoreException;
use NeuronAI\RAG\VectorSimilarity;
$a = [1.0, 0.0];
$b = [0.0, 1.0];
printf("Identical: %.1f\n", VectorSimilarity::cosineSimilarity($a, $a));
printf("Orthogonal: %.1f\n",
    VectorSimilarity::cosineSimilarity($a, $b));
printf("Opposite: %.1f\n",
    VectorSimilarity::cosineSimilarity($a, [-1.0, 0.0]));
try {
    VectorSimilarity::cosineSimilarity($a, [1.0]);
    throw new LogicException('Expected dimension rejection.');
} catch (VectorStoreException) {
    echo "Different dimensions rejected.\n";
}
echo "Vector contracts complete.\n";
