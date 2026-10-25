<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
use NeuronAI\StructuredOutput\Validation\Rules\GreaterThan;
final readonly class Order
{
    public function __construct(
        #[SchemaProperty(
            description: 'Every explicitly requested order line.',
            required: true,
            anyOf: [OrderLine::class],
        )]
        #[ArrayOf(OrderLine::class)]
        public array $lines,
        #[SchemaProperty(
            description: 'Name if stated.',
            required: false,
        )]
        public ?string $customerName = null,
        #[SchemaProperty(
            description: 'Stated EUR total in cents. Do not calculate.',
            required: false,
        )]
        #[GreaterThan(0)]
        public ?int $statedTotalCents = null,
        #[SchemaProperty(
            description: 'Street and ZIP if supplied, else null.',
            required: false,
        )]
        public ?Address $deliveryAddress = null,
    ) {
    }
}
