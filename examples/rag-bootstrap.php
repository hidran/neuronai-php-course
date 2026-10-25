<?php
declare(strict_types=1);
use App\Config\EmbeddingsFactory;
use App\Config\EmbeddingsSettings;
use App\Config\ProviderFactory;
use App\Config\RagServices;
use App\Config\VectorStoreFactory;
use App\Config\VectorStoreSettings;
$settings = require __DIR__ . '/bootstrap.php';
return new RagServices(
    settings: $settings,
    provider: new ProviderFactory($settings)->create(),
    embeddings: new EmbeddingsFactory(
        EmbeddingsSettings::fromEnv($_ENV))->create(),
    stores: new VectorStoreFactory(
        VectorStoreSettings::fromEnv($_ENV, dirname(__DIR__))),
);
