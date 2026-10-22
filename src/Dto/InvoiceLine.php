<?php
declare(strict_types=1);
namespace App\Dto;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\GreaterThan;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
class InvoiceLine
{
    #[SchemaProperty(description: 'Exact row description.')]
    #[NotBlank]
    public string $description;
    #[SchemaProperty(description: 'Quantity in units.')]
    #[GreaterThan(0)]
    public float $quantity;
    #[SchemaProperty(description: 'Unit price, excluding tax.')]
    public float $unit_price;
    #[SchemaProperty(description: 'Line total, excluding tax.')]
    public float $line_total;
}
