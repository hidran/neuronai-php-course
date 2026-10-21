<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
class Delivery
{
    #[SchemaProperty(description: 'Person name.', required: true)]
    public string $name;
    #[SchemaProperty(description: 'Delivery address.', required: true)]
    public Address $address;
    #[SchemaProperty(
        description: 'Delivery tags as stated.',
        required: true,
        anyOf: [Tag::class],
    )]
    #[ArrayOf(Tag::class, allowEmpty: true)]
    public array $tags;
    #[SchemaProperty(description: 'Keywords as stated.', required: true)]
    public array $keywords;
}
