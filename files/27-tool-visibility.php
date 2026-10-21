<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\PackageVersionTool;
use App\Tools\CountMessagesTool;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
Dotenv::createImmutable(dirname(__DIR__))->load();
$role = $argv[1] ?? 'user';
echo "Role: {$role}" . PHP_EOL;
$spy = new class(
    key: $_ENV['ANTHROPIC_KEY'],
    model: $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-5-5',
) extends Anthropic {
    public function setTools(array $tools): AIProviderInterface
    {
        $names = array_map(fn ($tool) => $tool->getName(), $tools);
        echo 'Tools sent: ' . implode(', ', $names) . PHP_EOL;
        return parent::setTools($tools);
    }
};
$file = dirname(__DIR__) . '/storage/assistant/chat.sqlite';
$counter = CountMessagesTool::make(new PDO("sqlite:{$file}"))
    ->visible($role === 'admin');
$state = AssistantAgent::make()
    ->setAiProvider($spy)
    ->addTool([PackageVersionTool::make(), $counter])
    ->setThreadId("visibility-{$role}")
    ->chat(new UserMessage('How many messages are on thread bob?'));
foreach ($state->getSteps() as $step) {
    if ($step instanceof ToolCallMessage) {
        foreach ($step->getToolCalls() as $call) {
            echo "Model called: {$call->getName()}" . PHP_EOL;
        }
    }
}
echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;