<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
final readonly class Delivery
{
    public function __construct(
        #[SchemaProperty(description: 'Person name.', required: true)]
        public string $name,
        #[SchemaProperty(
            description: 'Delivery address.',
            required: true,
        )]
        public Address $address,
        #[SchemaProperty(
            description: 'Tags as stated, each with its priority.',
            required: true,
            anyOf: [Tag::class],
        )]
        #[ArrayOf(Tag::class, allowEmpty: true)]
        public array $tags,
        #[SchemaProperty(
            description: 'Keywords as stated, one plain string each.',
            required: true,
        )]
        public array $keywords,
    ) {
    }
}
