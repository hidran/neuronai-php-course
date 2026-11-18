<?php
declare(strict_types=1);
namespace App\Report;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
use NeuronAI\StructuredOutput\Validation\Rules\GreaterThanEqual;
use NeuronAI\StructuredOutput\Validation\Rules\LowerThanEqual;
use NeuronAI\StructuredOutput\Validation\Rules\WordsCount;
final readonly class AuditReport
{
    public function __construct(
        #[SchemaProperty(description: 'At most two sentences.',
            required: true)]
        #[WordsCount(min: 5, max: 45)]
        public string $summary,
        #[SchemaProperty(description: 'The rubric score you received.',
            required: true)]
        #[GreaterThanEqual(0)]
        #[LowerThanEqual(100)]
        public int $score,
        #[SchemaProperty(description: 'Findings backed by evidence.',
            required: true, anyOf: [Finding::class])]
        #[ArrayOf(Finding::class, allowEmpty: true)]
        public array $findings,
        #[SchemaProperty(description: 'Every check of the evidence.',
            required: true, anyOf: [CheckStatus::class])]
        #[ArrayOf(CheckStatus::class)]
        public array $checks,
    ) {
    }
}
