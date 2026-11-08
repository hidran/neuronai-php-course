<?php
declare(strict_types=1);
namespace App\Support;
use Closure;
use RuntimeException;
// A small service container: get() builds a fresh object every time
// from the factory bound under that id.
final class Container
{
    /** @var array<string, Closure(self): object> */
    private array $factories = [];
    public function bind(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }
    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        $factory = $this->factories[$id]
            ?? throw new RuntimeException("Nothing bound for {$id}.");
        return $factory($this);
    }
}
