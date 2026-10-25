<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/Coupon.php';
require __DIR__ . '/CouponExtractor.php';
use App\Agents\CouponExtractor;
use App\Output\Coupon;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\Testing\FakeAIProvider;
$bad = '{"code":"ab1234","summary":"free shipping"}';
$good = '{"code":"AB1234","summary":"free shipping"}';
$fake = new FakeAIProvider(
    new AssistantMessage($bad), new AssistantMessage($good),
);
$coupon = CouponExtractor::make($fake)->setThreadId('t')
    ->structured(new UserMessage('AB1234 gives free shipping.'),
        Coupon::class, maxRetries: 1);
if (!$coupon instanceof Coupon || $coupon->code !== 'AB1234') {
    throw new RuntimeException('retry did not return a valid coupon');
}
if ($fake->getCallCount() !== 2) {
    throw new RuntimeException('expected two provider calls');
}
$messages = $fake->getRecorded()[1]->messages;
$correction = end($messages);
if (!str_contains((string) $correction->getContent(), 'problem')) {
    throw new RuntimeException('correction message not sent');
}
$fake = new FakeAIProvider(
    new AssistantMessage($bad), new AssistantMessage($bad),
);
try {
    CouponExtractor::make($fake)->setThreadId('u')
        ->structured(new UserMessage('x'), Coupon::class,
            maxRetries: 1);
    throw new RuntimeException('invalid coupon accepted');
} catch (AgentException $error) {
    if (!str_contains($error->getMessage(), 'code')) {
        throw new RuntimeException('violation not reported');
    }
}
if ($fake->getCallCount() !== 2) {
    throw new RuntimeException('retry budget not respected');
}
echo 'COUPON OK' . PHP_EOL;
