<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/EmbeddingsProvider.php';
require __DIR__ . '/EmbeddingsSettings.php';
require __DIR__ . '/EmbeddingsFactory.php';
use App\Config\EmbeddingsFactory;
use App\Config\EmbeddingsProvider;
use App\Config\EmbeddingsSettings;
use NeuronAI\RAG\Embeddings\GeminiEmbeddingsProvider;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;
$cases = [
    [['EMBEDDINGS_PROVIDER' => 'openai', 'EMBEDDINGS_MODEL' => 'm',
        'EMBEDDINGS_DIMENSIONS' => '1024', 'OPENAI_KEY' => 'k'],
        OpenAIEmbeddingsProvider::class, 1024],
    [['EMBEDDINGS_PROVIDER' => 'gemini', 'EMBEDDINGS_MODEL' => 'm',
        'GEMINI_KEY' => 'k'], GeminiEmbeddingsProvider::class, null],
    [['EMBEDDINGS_PROVIDER' => 'ollama', 'EMBEDDINGS_MODEL' => 'nomic'],
        OllamaEmbeddingsProvider::class, null],
];
foreach ($cases as [$env, $class, $dimensions]) {
    $settings = EmbeddingsSettings::fromEnv($env);
    if ($settings->dimensions !== $dimensions) {
        throw new RuntimeException('dimensions: ' . json_encode($env));
    }
    $provider = new EmbeddingsFactory($settings)->create();
    if (!$provider instanceof $class) {
        throw new RuntimeException("expected {$class}");
    }
}
if (EmbeddingsSettings::fromEnv(['EMBEDDINGS_MODEL' => 'nomic'])->provider
    !== EmbeddingsProvider::Ollama) {
    throw new RuntimeException('default provider must be ollama');
}
foreach ([
    [['EMBEDDINGS_PROVIDER' => 'openai', 'EMBEDDINGS_MODEL' => 'm'],
        'OPENAI_KEY is not set'],
    [['EMBEDDINGS_PROVIDER' => 'ollama'], 'EMBEDDINGS_MODEL is not set'],
    [['EMBEDDINGS_PROVIDER' => 'mistral', 'EMBEDDINGS_MODEL' => 'm'],
        'Unknown EMBEDDINGS_PROVIDER mistral'],
] as [$env, $message]) {
    try {
        EmbeddingsSettings::fromEnv($env);
        throw new LogicException("accepted: {$message}");
    } catch (RuntimeException $error) {
        if ($error->getMessage() !== $message) {
            throw $error;
        }
    }
}
echo 'EMBEDDINGS CONFIG OK' . PHP_EOL;
