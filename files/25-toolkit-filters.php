<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\Tools\CheckPackagesTool;
use App\Tools\FindPackagesTool;
use App\Tools\ProjectToolkit;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\Toolkits\Calculator\CalculatorToolkit;
use NeuronAI\Tools\Toolkits\Calculator\EvaluateTool;
use NeuronAI\Tools\Toolkits\Calculator\MeanTool;
use NeuronAI\Tools\Toolkits\Firecrawl\FirecrawlSearchTool;
use NeuronAI\Tools\Toolkits\Firecrawl\FirecrawlToolkit;
$settings = require __DIR__ . '/bootstrap.php';
$pdo = require __DIR__ . '/database.php';
$math = CalculatorToolkit::make()
    ->only([EvaluateTool::class, MeanTool::class]);
$project = new ProjectToolkit($pdo)
    ->exclude([CheckPackagesTool::class])
    ->add(new FindPackagesTool());
// Listing only: the Firecrawl key is not needed to read the schemas.
$web = FirecrawlToolkit::make('not-needed-for-listing')
    ->with(
        FirecrawlSearchTool::class,
        fn (ToolInterface $tool) => $tool->setName('firecrawl_search'),
    );
if ($argc < 2) {
    foreach ([$math, $project, $web] as $kit) {
        $names = $kit->tools()
            |> (fn (array $t) => array_map(
                fn (ToolInterface $tool) => $tool->getName(), $t,
            ));
        echo count($names) . ' tools: '
            . implode(', ', $names) . PHP_EOL;
    }
    foreach ($web->tools() as $tool) {
        $schema = json_encode($tool->getInputSchema());
        echo "{$tool->getName()}: {$schema}" . PHP_EOL;
    }
    exit;
}
$provider = new ProviderFactory($settings)->create();
$state = AssistantAgent::make($provider)
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
