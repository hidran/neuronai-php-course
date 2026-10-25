<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\CouponExtractor;
use App\Config\ProviderFactory;
use App\Output\Coupon;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
use NeuronAI\StructuredOutput\Validation\Validator;
$violations = Validator::validate(new Coupon('ab12', ''));
if (count($violations) !== 2) {
    throw new RuntimeException('Expected two local violations.');
}
echo 'Known invalid fixture: rejected' . PHP_EOL;
$valid = new Coupon(code: 'XY9876', summary: 'Free delivery');
if (Validator::validate($valid) !== []) {
    throw new RuntimeException('Expected valid local fixture.');
}
echo 'Known valid fixture: accepted' . PHP_EOL;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
try {
    $coupon = CouponExtractor::make($provider)->setThreadId('exercise')
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
