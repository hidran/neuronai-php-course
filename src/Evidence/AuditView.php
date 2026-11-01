<?php
declare(strict_types=1);
namespace App\Evidence;
use App\Target;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
final readonly class AuditView
{
    public const int MAX_FILES = 200;
    public const int MAX_FILE_BYTES = 32768;
    private const array SKIP_DIRS = ['.git', 'vendor', 'node_modules',
        'storage', 'var', 'build', 'cache'];
    private const string SECRET = '/^(\.env.*|.*\.(pem|key|p12|sqlite)'
        . '|id_rsa.*|auth\.json)$/i';
    private function __construct(
        public string $root,
        public array $files,
        public array $skipped,
        public bool $truncated,
    ) {
    }
    public static function prepare(Target $target, string $views): self
    {
        $safe = '/^[A-Za-z0-9_-][A-Za-z0-9._-]*$/';
        if (preg_match($safe, $target->label) !== 1) {
            throw new InvalidArgumentException('unsafe target label');
        }
        $root = "{$views}/{$target->label}";
        self::remove($root);
        $files = $skipped = [];
        $truncated = false;
        $found = self::walk($target->path, $skipped);
        foreach ($found as $relative => $info) {
            $reason = self::reason($relative, $info->getSize());
            if ($reason !== null) {
                $skipped[$reason] = ($skipped[$reason] ?? 0) + 1;
                continue;
            }
            if (count($files) === self::MAX_FILES) {
                $truncated = true;
                break;
            }
            @mkdir(dirname("{$root}/{$relative}"), 0o755, true);
            copy($info->getPathname(), "{$root}/{$relative}");
            $files[] = $relative;
        }
        @mkdir($root, 0o755, true);
        return new self(realpath($root), $files, $skipped, $truncated);
    }
    public function facts(): array
    {
        $has = fn (string $pattern): bool => array_find(
            $this->files,
            fn (string $file): bool => fnmatch($pattern, $file),
        ) !== null;
        return [
            'files' => count($this->files),
            'complete' => !$this->truncated,
            'skipped' => $this->skipped,
            'test_config' => $has('phpunit.xml*'),
            'tests_dir' => $has('tests/*'),
            'ci_workflow' => $has('.github/workflows/*.y*ml')
                || $has('.gitlab-ci.yml'),
            'scope' => 'files copied into the audit view',
        ];
    }
    private static function reason(string $relative, int $size): ?string
    {
        return match (true) {
            preg_match(self::SECRET, basename($relative)) === 1
                => 'secret_name',
            $size > self::MAX_FILE_BYTES => 'too_large',
            default => null,
        };
    }
    /** @return array<string, \SplFileInfo> */
    private static function walk(string $path, array &$skipped): array
    {
        $keep = static function (\SplFileInfo $info)
            use (&$skipped): bool {
            if ($info->isDir()
                && in_array($info->getFilename(), self::SKIP_DIRS,
                    true)) {
                $skipped['excluded_tree'] =
                    ($skipped['excluded_tree'] ?? 0) + 1;
                return false;
            }
            return true;
        };
        $files = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator(
                    $path,
                    FilesystemIterator::SKIP_DOTS,
                ),
                $keep,
            ),
        );
        $found = [];
        foreach ($files as $info) {
            if ($info->isFile() && !$info->isLink()) {
                $found[substr($info->getPathname(), strlen($path) + 1)]
                    = $info;
            }
        }
        ksort($found);
        return $found;
    }
    private static function remove(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $root,
                FilesystemIterator::SKIP_DOTS,
            ),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($root);
    }
}
