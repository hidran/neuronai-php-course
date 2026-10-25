<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Knowledge\Authority;
use App\Knowledge\AuthorityReport;
use App\Knowledge\AuthorityRouter;
use App\Knowledge\OrderDatabase;
$root = dirname(__DIR__);
$router = new AuthorityRouter([
    'direct context' => [
        'authority' => Authority::ShortStableText,
        'accepting' => ['input tokens on every request'],
    ],
    'retrieval' => [
        'authority' => Authority::Document,
        'accepting' => ['ingestion', 'retrieval evaluation'],
    ],
    'tools' => [
        'authority' => Authority::Record,
        'accepting' => ['validation', 'error handling',
            'authorization'],
    ],
    'fine tuning' => [
        'authority' => Authority::LearnedBehavior,
        'accepting' => ['training data', 'retraining on change'],
    ],
]);
$orders = new OrderDatabase($root . '/storage/knowledge/orders.sqlite');
new AuthorityReport($router, $orders->pdo())
    ->print($root . '/fixtures/knowledge/requests.json');
