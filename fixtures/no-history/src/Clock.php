<?php
declare(strict_types=1);
namespace Acme\Clock;
final class Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }
}
