<?php
declare(strict_types=1);
namespace App\Dto;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
use NeuronAI\StructuredOutput\Validation\Rules\Regex;
class Invoice
{
    #[SchemaProperty(description: 'Number exactly as printed.')]
    #[NotBlank]
    public string $number;
    #[SchemaProperty(description: 'Supplier legal name.')]
    #[NotBlank]
    public string $supplier_name;
    // Describe normalized date.
    // Describe currency.
    #[SchemaProperty(description: 'Subtotal excluding tax.')]
    public float $subtotal;
    #[SchemaProperty(
        description: 'Tax; zero only if explicitly tax exempt.',
    )]
    public float $vat_amount;
    #[SchemaProperty(description: 'Grand total including tax.')]
    public float $total;
    // Describe the nested line collection.
}
