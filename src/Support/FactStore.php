<?php
declare(strict_types=1);
namespace App\Support;
use InvalidArgumentException;
final readonly class FactStore
{
    public function __construct(private string $directory)
    {
    }
    public function remember(string $user, string $fact): void
    {
        $facts = $this->recall($user);
        $facts[] = $fact;
        $dir = $this->directory;
        is_dir($dir) || mkdir($dir, recursive: true);
        file_put_contents(
            $this->file($user),
            json_encode($facts, JSON_THROW_ON_ERROR),
        );
    }
    /** @return list<string> */
    public function recall(string $user): array
    {
        $file = $this->file($user);
        if (!is_file($file)) {
            return [];
        }
        $json = file_get_contents($file);
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }
    private function file(string $user): string
    {
        if (preg_match('/^[a-z0-9-]{1,32}$/', $user) !== 1) {
            throw new InvalidArgumentException("Bad user name {$user}");
        }
        return "{$this->directory}/facts_{$user}.json";
    }
}
