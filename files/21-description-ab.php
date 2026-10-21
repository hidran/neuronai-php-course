<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Tools\CountMessagesTool;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$tool = CountMessagesTool::make(new PDO('sqlite::memory:'));
$variants = [
    'vague' => 'Counts messages.',
    'class' => $tool->getDescription(),
    'precise' => 'Counts the chat messages our PHP assistant'
        . ' stored for one thread. Use it when the user asks'
        . ' how much someone has chatted with our assistant.'
        . ' Returns one sentence with the count. It knows'
        . ' nothing about email, SMS or other apps.',
];
$questions = [
    'How many messages are stored on thread bob?' => 'needed',
    'Has bob been chatting with our assistant a lot?' => 'needed',
    'How many emails has bob sent me?' => 'not needed',
    'How many texts did bob send me today?' => 'not needed',
];
$runs = 3;
foreach ($variants as $label => $description) {
    $tool->setDescription($description);
    $provider = AssistantAgent::make()->getProvider();
    $provider->systemPrompt('Answer in one short sentence.');
    $provider->setTools([$tool]);
    $tally = ['needed' => 0, 'not needed' => 0];
    foreach ($questions as $question => $expected) {
        $calls = 0;
        for ($i = 0; $i < $runs; $i++) {
            $reply = $provider->chat(new UserMessage($question))
                ->message();
            $calls += $reply instanceof ToolCallMessage ? 1 : 0;
        }
        $tally[$expected] += $calls;
        echo "{$label} {$calls}/{$runs} calls: {$question}" . PHP_EOL;
    }
    $max = $runs * 2;
    echo "Tally {$label}: {$tally['needed']}/{$max} when needed,"
        . " {$tally['not needed']}/{$max} when not needed" . PHP_EOL;
}