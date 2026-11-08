<?php
declare(strict_types=1);
namespace App\Support;
use InvalidArgumentException;
final readonly class SupportQuestion
{
    private function __construct(public string $text)
    {
    }
    public static function fromInput(string $input): self
    {
        $text = trim($input);
        if ($text === '' || mb_strlen($text) > 500) {
            throw new InvalidArgumentException(
                'A question needs 1 to 500 characters.');
        }
        return new self($text);
    }
}
