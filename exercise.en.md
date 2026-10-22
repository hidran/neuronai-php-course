# Validate a coupon before trusting it

Use the completed Coupon and CouponExtractor classes. Install the project's Composer dependencies and configure your own Anthropic credentials locally. Run all commands in PhpStorm Terminal.

1. Create a Coupon with code `ab12` and an empty summary. Validate it without calling a provider. Assert that it produces two violations.
2. Change its code to `XY9876` and its summary to `Free delivery`. Assert that it has no violations.
3. Extract from `XY9876 offers free delivery.` using `maxRetries: 0`. Catch the two structured-processing exception types if extraction fails.
4. On success, check that the returned code equals the code in the source. Explain why the regular expression alone cannot establish that fact.
5. Explain the maximum number of attempts with retry budgets zero, one and two.

## Questions

1. Why can `ab12` be a PHP string and still fail the output contract? What happens to an empty required summary?
2. Does calling `Validator::validate()` contact the provider or retry? How is that different from `structured()`?
3. How many attempts can `maxRetries: 0` make? Would a different well-formed code prove a faithful extraction?

## Worked solution

Save as `examples/exercise-validation-retry.php`:

```php
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
```

Run `php examples/exercise-validation-retry.php` in PhpStorm Terminal. A successful verified run prints:

```text
Known invalid fixture: rejected
Known valid fixture: accepted
Returned code: XY9876
Summary: Free delivery
Exercise complete.
```

The summary wording can vary. The empty required summary violates the minimum word count; the malformed code violates Regex. Direct validation returns messages without model calls. The live request allows one attempt, because there are zero retries. Budgets one and two allow at most two and three attempts respectively. A well-formed invented code can pass Regex, so the solution separately checks equality with the source code. A processing failure takes the explicit failure path; a configuration or network error is outside this specific catch.
