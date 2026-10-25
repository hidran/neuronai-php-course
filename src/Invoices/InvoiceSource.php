<?php
declare(strict_types=1);
namespace App\Invoices;
use finfo;
use InvalidArgumentException;
use NeuronAI\Chat\Enums\MediaType;
final readonly class InvoiceSource
{
    private const int MAX_BYTES = 3_000_000;
    private const array MIMES = [
        'application/pdf' => MediaType::PDF,
        'image/png' => MediaType::PNG,
    ];
    public function __construct(
        public string $name,
        public MediaType $mediaType,
        public string $bytes,
        public string $sha256,
    ) {
    }
    public static function fromDirectory(string $dir, string $name)
        : self
    {
        if ($name === '' || basename($name) !== $name) {
            throw new InvalidArgumentException('Use a bare file name.');
        }
        $path = $dir . '/' . $name;
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException("Unreadable: {$name}");
        }
        $bytes = file_get_contents($path);
        if ($bytes === false || $bytes === ''
            || strlen($bytes) > self::MAX_BYTES) {
            throw new InvalidArgumentException("Size: {$name}");
        }
        $mime = new finfo(FILEINFO_MIME_TYPE)->buffer($bytes);
        $mediaType = self::MIMES[$mime]
            ?? throw new InvalidArgumentException("Type: {$mime}");
        return new self($name, $mediaType, $bytes,
            hash('sha256', $bytes));
    }
}
