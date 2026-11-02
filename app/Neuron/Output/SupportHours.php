<?php
declare(strict_types=1);
namespace App\Neuron\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
final readonly class SupportHours
{
    public function __construct(
        #[SchemaProperty(
            description: 'Opening days, like Monday to Friday.',
            required: true,
        )]
        public string $days,
        #[SchemaProperty(
            description: 'Opening time, 24-hour HH:MM.',
            required: true,
        )]
        public string $opens,
        #[SchemaProperty(
            description: 'Closing time, 24-hour HH:MM.',
            required: true,
        )]
        public string $closes,
    ) {
    }
}
