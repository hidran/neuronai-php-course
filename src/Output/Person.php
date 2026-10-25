<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
final readonly class Person
{
    public function __construct(
        #[SchemaProperty(
            description: 'Name exactly as written. Do not complete it.',
            required: true,
            minLength: 3,
            maxLength: 80,
        )]
        public string $name,
        #[SchemaProperty(
            description: 'Favorite food stated in the source text.',
            required: true,
        )]
        public string $preference,
        #[SchemaProperty(
            description: 'Age in whole years, only if stated.',
            required: false,
            min: 18,
            max: 120,
        )]
        public ?int $age = null,
    ) {
    }
}
