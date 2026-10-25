<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
final readonly class Tag
{
    public function __construct(
        #[SchemaProperty(description: 'Tag as stated.', required: true)]
        #[NotBlank]
        public string $name,
        #[SchemaProperty(
            description: 'Priority as stated, 1 is highest.',
            required: true,
            min: 1,
            max: 3,
        )]
        public int $priority,
    ) {
    }
}
