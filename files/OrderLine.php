<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\GreaterThan;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
final readonly class OrderLine
{
    public function __construct(
        #[SchemaProperty(
            description: 'SKU exactly as stated, even if malformed.',
            required: true,
        )]
        #[NotBlank]
        public string $sku,
        #[SchemaProperty(
            description: 'Explicit quantity.',
            required: true,
        )]
        #[GreaterThan(0)]
        public int $quantity,
        #[SchemaProperty(
            description: 'Stated EUR unit price in cents. Never infer.',
            required: false,
        )]
        #[GreaterThan(0)]
        public ?int $unitPriceCents = null,
    ) {
    }
}
