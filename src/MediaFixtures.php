<?php
declare(strict_types=1);
namespace App;
use finfo;
use RuntimeException;
final readonly class MediaFixtures
{
    private const int MAX_BYTES = 1_000_000;
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
        if ($size === false || $size < 1 || $size > self::MAX_BYTES) {
            throw new RuntimeException('Fixture exceeds local limits.');
        }
        $actual = new finfo(FILEINFO_MIME_TYPE)->file($path);
        if ($actual !== $mime) {
            throw new RuntimeException('Fixture MIME type differs.');
        }
        return $path;
    }
    public function encoded(string $name, string $mime): string
    {
        $bytes = file_get_contents($this->path($name, $mime));
        if ($bytes === false) {
            throw new RuntimeException("Reading {$name} failed.");
        }
        return base64_encode($bytes);
    }
    /** @return list<string> */
    public function facts(string $kind): array
    {
        $json = file_get_contents($this->directory . '/expected.json')
            ?: throw new RuntimeException('expected.json missing.');
        $facts = json_decode($json, associative: true,
            flags: JSON_THROW_ON_ERROR);
        return $kind === 'image'
            ? [$facts['image']['triangle_color'],
                $facts['image']['triangle_label']]
            : [$facts['pdf']['dispatch_code']];
    }
    public function verify(string $reply, string $kind): void
    {
        foreach ($this->facts($kind) as $fact) {
            if (!str_contains(self::normalize($reply),
                self::normalize($fact))) {
                throw new RuntimeException(
                    "Attachment fact missing: {$fact}"
                );
            }
        }
    }
    public static function normalize(string $text): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $text));
    }
}
