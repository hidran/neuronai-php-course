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
