<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Tools\CountMessagesTool;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Tools\ToolOutput;
Dotenv::createImmutable(dirname(__DIR__))->load();
$pdo = new PDO('sqlite::memory:');
$safe = new class ($pdo) extends CountMessagesTool {
    public function __invoke(string $thread): ToolOutput
    {
        try {
            return parent::__invoke($thread);
        } catch (PDOException) {
            return ToolOutput::error('The chat database is not'
                . ' set up yet. Tell the user, and do not retry.');
        }
    }
};
$mode = $argv[1] ?? 'plain';
echo "Mode: {$mode}" . PHP_EOL;
$tool = $mode === 'safe' ? $safe : CountMessagesTool::make($pdo);
$agent = AssistantAgent::make()
    ->addTool($tool)
    ->setThreadId('tool-errors');
try {
    $question = 'How many messages are stored on thread bob?';
    $state = $agent->chat(new UserMessage($question));
    foreach ($state->getSteps() as $step) {
        echo 'Step: ' . $step::class . PHP_EOL;
        if ($step instanceof ToolResultMessage) {
            foreach ($step->getToolCalls() as $call) {
                $json = json_encode($call->getResult());
                echo "Tool result: {$json}" . PHP_EOL;
            }
        }
    }
    echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
} catch (PDOException $e) {
    echo "Run aborted: {$e->getMessage()}" . PHP_EOL;
}
