<?php
declare(strict_types=1);
namespace App\Report;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
use NeuronAI\StructuredOutput\Validation\Rules\Enum;
use NeuronAI\StructuredOutput\Validation\Rules\WordsCount;
final readonly class Finding
{
    public function __construct(
        #[SchemaProperty(description: 'high, medium or low.',
            required: true)]
        #[Enum(values: ['high', 'medium', 'low'])]
        public string $severity,
        #[SchemaProperty(description: 'testing, ci, dependencies, '
            . 'structure or history.', required: true)]
        #[Enum(values: ['testing', 'ci', 'dependencies', 'structure',
            'history'])]
        public string $category,
        #[SchemaProperty(description: 'The observed fact.',
            required: true)]
        #[WordsCount(min: 3, max: 25)]
        public string $description,
        #[SchemaProperty(description: 'One concrete action.',
            required: true)]
        #[WordsCount(min: 3, max: 25)]
        public string $recommendation,
        #[SchemaProperty(description: 'Evidence ids, such as manifest.',
            required: true)]
        #[ArrayOf('string')]
        public array $evidence,
        #[SchemaProperty(description: 'File or package, if any.',
            required: false)]
        public ?string $subject = null,
    ) {
    }
}
