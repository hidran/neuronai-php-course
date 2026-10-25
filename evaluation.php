<?php
declare(strict_types=1);
use NeuronAI\Evaluation\Output\ConsoleOutput;
use NeuronAI\Evaluation\Output\JsonOutput;
$path = __DIR__ . '/storage/evaluation';
if (!is_dir($path)) {
    mkdir($path, 0755, true);
}
require __DIR__ . '/examples/bootstrap.php';
return ['output' => [new ConsoleOutput(),
    new JsonOutput($path . '/cli-results.json')]];
