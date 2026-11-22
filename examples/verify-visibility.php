<?php

declare(strict_types=1);
$lab = $argv[1] ?? dirname(__DIR__);
require $lab . '/vendor/autoload.php';
// PackageVersionTool.php autoloads from src/PackageVersionTool.php
// CountMessagesTool.php autoloads from src/Tools/CountMessagesTool.php
// AssistantAgent.php autoloads from src/Agents/AssistantAgent.php

use App\Agents\AssistantAgent;
use App\PackageVersionTool;
use App\Tools\CountMessagesTool;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;

$pdo = new PDO('sqlite::memory:');
foreach (['user' => ['get_package_version'], 'admin' => ['get_package_version', 'count_messages']] as $role => $expected) {
    $fake = new FakeAIProvider(new AssistantMessage('ok'));
    $counter = new CountMessagesTool($pdo)->visible($role === 'admin');
    AssistantAgent::make($fake)
        ->addTool([new PackageVersionTool(), $counter])
        ->setThreadId("v-{$role}")
        ->chat(new UserMessage('How many messages?'));
    $sent = array_map(fn($t) => $t->getName(), $fake->getRecorded()[0]->tools ?? []);
    sort($sent);
    sort($expected);
    if ($sent !== $expected) {
        throw new RuntimeException("{$role}: sent " . json_encode($sent));
    }
}
echo 'VISIBILITY OK' . PHP_EOL;
