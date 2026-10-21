<?php
declare(strict_types=1);
namespace App\Dto;
use NeuronAI\StructuredOutput\SchemaProperty;
class PackageFilter
{
    #[SchemaProperty(description: 'The vendor, like symfony.')]
    public string $vendor;
    #[SchemaProperty(description: 'How many packages at most.')]
    public int $limit = 5;
}
