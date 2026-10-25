<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\Regex;
use NeuronAI\StructuredOutput\Validation\Rules\WordsCount;
final readonly class Coupon
{
    public function __construct(
        #[SchemaProperty(
            description: 'Two uppercase letters and four digits.',
            required: true,
        )]
        #[Regex('/^[A-Z]{2}[0-9]{4}$/')]
        public string $code,
        #[SchemaProperty(
            description: 'Short offer summary.',
            required: true,
        )]
        #[WordsCount(min: 1, max: 6)]
        public string $summary,
    ) {
    }
}
