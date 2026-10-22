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
$candidate->code = 'bad';
$candidate->summary = 'This special offer has far too many words';
foreach (Validator::validate($candidate) as $violation) {
    echo $violation . PHP_EOL;
}
echo 'Local validation complete.' . PHP_EOL;
Dotenv::createImmutable(dirname(__DIR__))->load();
$agent = CouponExtractor::make()->setThreadId('coupon');
try {
    $coupon = $agent->structured(
        messages: new UserMessage('AB1234 gives free shipping.'),
        class: Coupon::class,
        maxRetries: 1,
    );
    echo 'Code: ' . $coupon->code . PHP_EOL;
    echo 'Summary: ' . $coupon->summary . PHP_EOL;
} catch (AgentException | DeserializerException $error) {
    echo 'No valid coupon: ' . $error->getMessage() . PHP_EOL;
}
echo 'Bounded extraction complete.' . PHP_EOL;
