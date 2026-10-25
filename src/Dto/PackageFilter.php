<?php
declare(strict_types=1);
namespace App\Dto;
use NeuronAI\StructuredOutput\SchemaProperty;
final readonly class PackageFilter
{
    public function __construct(
        #[SchemaProperty(description: 'The vendor, like symfony.')]
        public string $vendor,
        #[SchemaProperty(
            description: 'How many packages at most.',
            min: 1,
            max: 20,
        )]
        public int $limit = 5,
    ) {
    }
}
