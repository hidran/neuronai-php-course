<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\NeuronException;
Dotenv::createImmutable(dirname(__DIR__))->load();
$factory = new ProviderFactory(Settings::fromEnv($_ENV));
$question = 'Who created PHP, and when?';
function ask(ProviderFactory $factory, array $params, string $q): string
{
    $start = hrtime(true);
    try {
        $state = HelloAgent::make($factory->create($params))
            ->setThreadId(bin2hex(random_bytes(4)))
            ->chat(new UserMessage($q));
    } catch (NeuronException $e) {
        echo 'Rejected by the provider: ' . $e->getMessage() . PHP_EOL;
        return '';
    }
    $ms = intdiv(hrtime(true) - $start, 1_000_000);
    $answer = $state->getMessage()?->getContent() ?? '';
    echo mb_strlen($answer) . " chars, $ms ms: $answer" . PHP_EOL;
    return $answer;
}
$answers = [];
for ($run = 1; $run <= 5; $run++) {
    echo "Run $run of 5, default temperature" . PHP_EOL;
    $answers[] = ask($factory, [], $question);
}
$different = count(array_unique($answers));
$withFact = count(array_filter(
    $answers, fn (string $a) => str_contains($a, 'Lerdorf'),
));
echo "Done: $different different texts, "
    . "$withFact of 5 mention Lerdorf" . PHP_EOL;
echo 'Run 6, temperature 0' . PHP_EOL;
ask($factory, ['temperature' => 0], $question);
echo 'Run 7, temperature 0' . PHP_EOL;
ask($factory, ['temperature' => 0], $question);
