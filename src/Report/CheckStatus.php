<?php
declare(strict_types=1);
namespace App\Report;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\Enum;
final readonly class CheckStatus
{
    public function __construct(
        #[SchemaProperty(description: 'The check name.',
            required: true)]
        public string $check,
        #[SchemaProperty(description: 'The status in the evidence.',
            required: true)]
        #[Enum(values: ['ok', 'clean', 'findings', 'unavailable'])]
        public string $status,
        #[SchemaProperty(description: 'The evidence id.',
            required: true)]
        public string $evidence,
    ) {
    }
}
