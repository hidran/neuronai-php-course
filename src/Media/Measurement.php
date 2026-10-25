<?php
declare(strict_types=1);
namespace App\Media;
final readonly class Measurement
{
    public function __construct(
        public string $dimensions,
        public int $rawBytes,
        public int $base64Bytes,
        public string $encoded,
    ) {
    }
}
