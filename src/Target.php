<?php
declare(strict_types=1);
namespace App;
use InvalidArgumentException;
final readonly class Target
{
    private function __construct(
        public string $path,
        public string $label,
    ) {
    }
    public static function fromArgument(?string $argument): self
    {
        if ($argument === null || $argument === '') {
            throw new InvalidArgumentException('no target directory');
        }
        $path = realpath($argument);
        if ($path === false || !is_dir($path)) {
            throw new InvalidArgumentException(
                "not a directory: {$argument}"
            );
        }
        if (!is_readable($path)) {
            throw new InvalidArgumentException(
                "not readable: {$argument}"
            );
        }
        return new self($path, basename($path));
    }
    public function has(string $relative): bool
    {
        return file_exists("{$this->path}/{$relative}");
    }
}
