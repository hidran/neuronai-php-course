<?php
declare(strict_types=1);
namespace App\Media;
final readonly class ImageCase
{
    public function __construct(
        public string $name,
        public string $file,
        public int $width,
        public int $height,
        public string $sha256,
    ) {
    }
}
