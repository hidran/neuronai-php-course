<?php
declare(strict_types=1);
namespace App\Invoices;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
use NeuronAI\StructuredOutput\Validation\Rules\Regex;
final readonly class Invoice
{
    public function __construct(
        #[SchemaProperty(
            description: 'Invoice number exactly as printed.',
            required: true,
        )]
        #[NotBlank]
        public string $number,
        #[SchemaProperty(
            description: 'Supplier legal name.',
            required: true,
        )]
        #[NotBlank]
        public string $supplierName,
        #[SchemaProperty(
            description: 'Issue date as YYYY-MM-DD.',
            required: true,
        )]
        #[Regex('/^\d{4}-\d{2}-\d{2}$/')]
        public string $issueDate,
        #[SchemaProperty(
            description: 'Currency code printed on the invoice.',
            required: true,
        )]
        public Currency $currency,
        #[SchemaProperty(
            description: 'Printed subtotal in cents, excluding tax.',
            required: true,
        )]
        public int $subtotalCents,
        #[SchemaProperty(
            description: 'Printed tax in cents, zero only if exempt.',
            required: true,
        )]
        public int $vatCents,
        #[SchemaProperty(
            description: 'Printed grand total in cents.',
            required: true,
        )]
        public int $totalCents,
        #[SchemaProperty(
            description: 'All rows in order, including later pages.',
            required: true,
            anyOf: [InvoiceLine::class],
        )]
        #[ArrayOf(InvoiceLine::class)]
        public array $lines,
    ) {
    }
}
