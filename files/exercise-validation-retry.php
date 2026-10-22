<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\CouponExtractor;
use App\Output\Coupon;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
use NeuronAI\StructuredOutput\Validation\Validator;
$candidate = new Coupon();
$candidate->code = 'ab12';
$candidate->summary = '';
$violations = Validator::validate($candidate);
if (count($violations) !== 2) {
    throw new RuntimeException('Expected two local violations.');
}
echo 'Known invalid fixture: rejected' . PHP_EOL;
$candidate->code = 'XY9876';
$candidate->summary = 'Free delivery';
if (Validator::validate($candidate) !== []) {
    throw new RuntimeException('Expected valid local fixture.');
}
echo 'Known valid fixture: accepted' . PHP_EOL;
Dotenv::createImmutable(dirname(__DIR__))->load();
try {
    $coupon = CouponExtractor::make()->setThreadId('exercise')
        ->structured(
            messages: new UserMessage('XY9876 offers free delivery.'),
            class: Coupon::class,
            maxRetries: 0,
        );
    if ($coupon->code !== 'XY9876') {
        throw new RuntimeException('Code differs from source.');
    }
    echo 'Returned code: ' . $coupon->code . PHP_EOL;
    echo 'Summary: ' . $coupon->summary . PHP_EOL;
} catch (AgentException | DeserializerException $error) {
    echo 'Single attempt failed: ' . $error->getMessage() . PHP_EOL;
}
echo 'Exercise complete.' . PHP_EOL;
