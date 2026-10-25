<?php
declare(strict_types=1);
namespace App\Invoices;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\GreaterThan;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
final readonly class InvoiceLine
{
    public function __construct(
        #[SchemaProperty(
            description: 'Row description exactly as printed.',
            required: true,
        )]
        #[NotBlank]
        public string $description,
        #[SchemaProperty(
            description: 'Quantity in whole units.',
            required: true,
        )]
        #[GreaterThan(0)]
        public int $quantity,
        #[SchemaProperty(
            description: 'Unit price in cents, excluding tax.',
            required: true,
        )]
        public int $unitPriceCents,
        #[SchemaProperty(
            description: 'Printed line total in cents, excluding tax.',
            required: true,
        )]
        public int $lineTotalCents,
    ) {
    }
}
