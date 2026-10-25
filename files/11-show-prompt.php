<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use NeuronAI\Testing\FakeAIProvider;
$agent = AssistantAgent::make(new FakeAIProvider());
$instructions = $agent->getInstructions();
echo $instructions->getContent() . PHP_EOL;
echo 'Sent as: ' . $instructions::class . PHP_EOL;
