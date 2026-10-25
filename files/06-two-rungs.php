<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\HelloAgent;
use Composer\InstalledVersions;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$provider = new ProviderFactory(Settings::fromEnv($_ENV))->create();
function run(string $label, HelloAgent $agent, string $ask): void
{
    $start = hrtime(true);
    $state = $agent->setThreadId($label)
        ->chat(new UserMessage($ask));
    $ms = intdiv(hrtime(true) - $start, 1_000_000);
    echo "[$label] " . $state->getMessage()?->getContent() . PHP_EOL;
    echo "[$label] steps: " . count($state->getSteps())
        . ", time: $ms ms" . PHP_EOL;
}
$package = 'neuron-core/neuron-ai';
$task = 'Say it, and what each of its three numbers means.';
$version = InstalledVersions::getPrettyVersion($package);
run('single-call', HelloAgent::make($provider)->setTools([]),
    "{$package} is at version {$version}. {$task}");
run('agent', HelloAgent::make($provider),
    "Which {$package} version is installed? {$task}");
