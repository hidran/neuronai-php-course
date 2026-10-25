<?php
declare(strict_types=1);
namespace App\Media;
use NeuronAI\StructuredOutput\SchemaProperty;
final readonly class CostProbe
{
    public function __construct(
        #[SchemaProperty(
            description: 'The heading printed on the card, or null.',
            required: false,
        )]
        public ?string $heading = null,
        #[SchemaProperty(
            description: 'The small audit seal label, or null.',
            required: false,
        )]
        public ?string $smallLabel = null,
        #[SchemaProperty(
            description: 'The quantity of the requested row, or null.',
            required: false,
        )]
        public ?int $quantity = null,
    ) {
    }
}
