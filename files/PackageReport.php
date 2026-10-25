<?php
declare(strict_types=1);
namespace App\Output;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
final readonly class PackageReport
{
    public function __construct(
        #[SchemaProperty(
            description: 'Package name as vendor/name.',
            required: true,
        )]
        #[NotBlank]
        public string $package,
        #[SchemaProperty(
            description: 'Installed version exactly as the tool said.',
            required: true,
        )]
        #[NotBlank]
        public string $version,
        #[SchemaProperty(
            description: 'One sentence on what the package does.',
            required: true,
        )]
        public string $summary,
    ) {
    }
}
