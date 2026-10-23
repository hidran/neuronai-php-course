<?php
declare(strict_types=1);
namespace App\Observability;
use Psr\Log\AbstractLogger;
use Stringable;
class EventFileLogger extends AbstractLogger
{
    public function __construct(private string $path)
    {
    }
    public function log($level, string|Stringable $message,
        array $context = []): void
    {
        // Deliberately retain event names only in this demo.
        file_put_contents($this->path, $level . ' ' . $message
            . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
