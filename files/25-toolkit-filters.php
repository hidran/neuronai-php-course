<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Tools\CheckPackagesTool;
use App\Tools\FindPackagesTool;
use App\Tools\ProjectToolkit;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\Toolkits\Calculator\CalculatorToolkit;
use NeuronAI\Tools\Toolkits\Calculator\EvaluateTool;
use NeuronAI\Tools\Toolkits\Calculator\MeanTool;
use NeuronAI\Tools\Toolkits\Firecrawl\FirecrawlSearchTool;
use NeuronAI\Tools\Toolkits\Firecrawl\FirecrawlToolkit;
Dotenv::createImmutable(dirname(__DIR__))->load();
$math = CalculatorToolkit::make()
    ->only([EvaluateTool::class, MeanTool::class]);
$file = dirname(__DIR__) . '/storage/assistant/chat.sqlite';
$project = ProjectToolkit::make(new PDO("sqlite:{$file}"))
    ->exclude([CheckPackagesTool::class])
    ->add(FindPackagesTool::make());
$web = FirecrawlToolkit::make($_ENV['FIRECRAWL_KEY'] ?? '')
    ->with(
        FirecrawlSearchTool::class,
        fn (ToolInterface $tool) => $tool->setName('firecrawl_search'),
    );
if ($argc < 2) {
    foreach ([$math, $project, $web] as $kit) {
        $names = array_map(
            fn (ToolInterface $tool) => $tool->getName(),
            $kit->tools(),
        );
        echo count($names) . ' tools: '
            . implode(', ', $names) . PHP_EOL;
    }
    foreach ($web->tools() as $tool) {
        $schema = json_encode($tool->getInputSchema());
        echo "{$tool->getName()}: {$schema}" . PHP_EOL;
    }
    exit;
}
$state = AssistantAgent::make()
    ->addTool([$math, $project])
    ->setThreadId('toolkit-filters')
    ->chat(new UserMessage($argv[1]));
foreach ($state->getSteps() as $step) {
    if ($step instanceof ToolCallMessage) {
        foreach ($step->getToolCalls() as $call) {
            echo "Model called: {$call->getName()}" . PHP_EOL;
        }
    }
}
echo $state->getMessage()?->getContent() . PHP_EOL;
