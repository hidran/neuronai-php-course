<?php
declare(strict_types=1);
namespace Fixture;
final readonly class Greeting
{
    public function __construct(private string $name)
    {
    }
    public function text(): string
    {
        return "Hello, {$this->name}.";
    }
}
