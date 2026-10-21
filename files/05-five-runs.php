<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\HelloAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$question = 'Who created PHP, and when?';
$answers = [];
$withFact = 0;
for ($run = 1; $run <= 5; $run++) {
    $start = hrtime(true);
    $state = HelloAgent::make()
        ->setThreadId("run-$run")
        ->chat(new UserMessage($question));
    $ms = intdiv(hrtime(true) - $start, 1_000_000);
    $answer = $state->getMessage()?->getContent() ?? '';
    $answers[] = $answer;
    if (str_contains($answer, 'Lerdorf')) {
        $withFact++;
    }
    echo "Run $run of 5: " . mb_strlen($answer) . " chars, $ms ms"
        . PHP_EOL . $answer . PHP_EOL;
}
$different = count(array_unique($answers));
echo "Done: $different different texts, "
    . "$withFact of 5 mention Lerdorf" . PHP_EOL;
