<?php
declare(strict_types=1);
use App\Knowledge\OrderDatabase;
use App\Knowledge\SupportAgent;
use App\Support\Container;
use NeuronAI\Providers\AIProviderInterface;
// The application's wiring: configuration in, services out.
$rag = require __DIR__ . '/rag-bootstrap.php';
$root = dirname(__DIR__);
$container = new Container();
$container->bind(AIProviderInterface::class, fn () => $rag->provider);
$container->bind(SupportAgent::class, fn (Container $c) =>
    new SupportAgent(
        $c->get(AIProviderInterface::class),
        $rag->embeddings,
        $rag->stores->create('support-policy'),
        new OrderDatabase($root . '/storage/knowledge/orders.sqlite')
            ->pdo(),
        'C-118',
    ));
return $container;
