<?php
declare(strict_types=1);
namespace App;
use finfo;
use RuntimeException;
class MediaFixtures
{
    public function __construct(private string $directory)
    {
    }
    public function path(string $name, string $mime): string
    {
        if (basename($name) !== $name) {
            throw new RuntimeException('Use a fixture filename.');
        }
        $path = $this->directory . '/' . $name;
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Fixture is not readable.');
        }
        $size = filesize($path);
        if ($size === false || $size < 1 || $size > 1_000_000) {
            throw new RuntimeException('Fixture exceeds local limits.');
        }
        $actual = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if ($actual !== $mime) {
            throw new RuntimeException('Fixture MIME type differs.');
        }
        return $path;
    }
    public function verify(string $reply, string $kind): void
    {
        $facts = json_decode(
            file_get_contents($this->directory . '/expected.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $needed = $kind === 'image'
            ? [$facts['image']['triangle_color'],
                $facts['image']['triangle_label']]
            : [$facts['pdf']['dispatch_code']];
        $normalize = static fn (string $s): string => strtolower(
            preg_replace('/[^a-zA-Z0-9]/', '', $s)
        );
        foreach ($needed as $fact) {
            if (!str_contains($normalize($reply), $normalize($fact))) {
                throw new RuntimeException('Attachment fact missing.');
            }
        }
        echo ucfirst($kind) . ' fixture complete.' . PHP_EOL;
    }
}
