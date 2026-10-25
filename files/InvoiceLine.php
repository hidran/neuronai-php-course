<?php
declare(strict_types=1);
namespace App\Deterministic;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\GreaterThan;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
final readonly class InvoiceLine
{
    public function __construct(
        #[SchemaProperty(description: 'Row text.', required: true)]
        #[NotBlank]
        public string $description,
        #[SchemaProperty(description: 'Whole units.', required: true)]
        #[GreaterThan(0)]
        public int $quantity,
        #[SchemaProperty(description: 'Unit cents.', required: true)]
        public int $unitPriceCents,
    ) {
    }
}
