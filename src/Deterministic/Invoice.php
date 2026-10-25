<?php
declare(strict_types=1);
namespace App\Deterministic;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
final readonly class Invoice
{
    /** @param list<InvoiceLine> $lines */
    public function __construct(
        #[SchemaProperty(
            description: 'All invoice rows.',
            required: true,
            anyOf: [InvoiceLine::class],
        )]
        #[ArrayOf(InvoiceLine::class)]
        public array $lines,
        #[SchemaProperty(description: 'Subtotal cents.', required: true)]
        public int $subtotalCents,
        #[SchemaProperty(description: 'Tax in cents.', required: true)]
        public int $taxCents,
        #[SchemaProperty(description: 'Total cents.', required: true)]
        public int $totalCents,
        #[SchemaProperty(description: 'Tax exempt.', required: true)]
        public bool $taxExempt,
    ) {
    }
}
