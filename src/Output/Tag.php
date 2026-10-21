<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
class Tag
{
    #[SchemaProperty(description: 'Tag as stated.', required: true)]
    #[NotBlank]
    public string $name;
}
