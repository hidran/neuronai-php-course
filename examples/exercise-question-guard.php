<?php
// Exercise solution: a question guard that refuses pasted credentials, with its offline checks.
// Run from the project root: php examples/exercise-question-guard.php (no key, no model).
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use App\Support\SupportQuestion;

final readonly class GuardedQuestion
{
    private const string CREDENTIAL = '/\b(sk-[A-Za-z0-9_-]{8,}|[A-Z][A-Z0-9_]*_(KEY|TOKEN|SECRET)\s*=)/';

    private function __construct(public string $text)
    {
    }

    public static function fromInput(string $input): self
    {
        $question = SupportQuestion::fromInput($input);
        if (preg_match(self::CREDENTIAL, $question->text) === 1) {
            throw new InvalidArgumentException('Remove the credential from the question.');
        }
        return new self($question->text);
    }
}

function rejected(string $input): bool
{
    try {
        GuardedQuestion::fromInput($input);
        return false;
    } catch (InvalidArgumentException) {
        return true;
    }
}

$cases = [
    ['How long do refunds take?', false],
    ['   ', true],
    ['My key is sk-ant-abc123def456, why does it fail?', true],
    ['I set OPENAI_API_KEY=abc in my file, is that right?', true],
    ['What is a skill key point for agents?', false],
];
$failures = 0;
foreach ($cases as [$input, $expected]) {
    $ok = rejected($input) === $expected;
    $failures += $ok ? 0 : 1;
    echo ($ok ? 'ok   ' : 'FAIL ') . ($expected ? 'rejected: ' : 'accepted: ') . $input . PHP_EOL;
}
echo $failures === 0 ? 'Exercise complete.' . PHP_EOL : "{$failures} case(s) failed." . PHP_EOL;
exit($failures === 0 ? 0 : 1);
