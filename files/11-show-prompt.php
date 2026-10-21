<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
$instructions = AssistantAgent::make()->getInstructions();
echo $instructions->getContent() . PHP_EOL;
echo 'Sent as: ' . $instructions::class . PHP_EOL;