<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
class Address
{
    #[SchemaProperty(description: 'Street as stated.', required: true)]
    #[NotBlank]
    public string $street;
    #[SchemaProperty(description: 'ZIP as stated.', required: true)]
    #[NotBlank]
    public string $zip;
    #[SchemaProperty(description: 'City if stated.', required: false)]
    public ?string $city = null;
}
