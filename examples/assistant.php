<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
use App\Agents\AssistantAgent;
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\PackageVersionTool;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\NeuronException;
Dotenv::createImmutable(__DIR__)->safeLoad();
try {
    $settings = Settings::fromEnv($_ENV);
} catch (RuntimeException $e) {
    fwrite(STDERR, "Configuration error: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}
$agent = AssistantAgent::make(new ProviderFactory($settings)->create())
    ->addTool(new PackageVersionTool())
    ->setThreadId('cli');
function ask(AssistantAgent $agent, Settings $settings, string $q): void
{
    try {
        $state = $agent->chat(new UserMessage($q));
    } catch (NeuronException $e) {
        fwrite(STDERR, "Error: {$e->getMessage()}" . PHP_EOL);
        return;
    }
    echo $state->getMessage()?->getContent() . PHP_EOL;
    echo "Answered by: {$settings->provider->value}, "
        . $settings->model . PHP_EOL;
}
if (isset($argv[1])) {
    ask($agent, $settings, $argv[1]);
    exit(0);
}
echo "Chatting on {$settings->provider->value}. Type exit to quit."
    . PHP_EOL;
echo 'You: ';
while (($line = fgets(STDIN)) !== false) {
    $question = trim($line);
    if ($question === 'exit') {
        break;
    }
    if ($question !== '') {
        ask($agent, $settings, $question);
    }
    echo 'You: ';
}
echo 'Bye.' . PHP_EOL;
