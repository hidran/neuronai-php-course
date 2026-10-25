<?php
declare(strict_types=1);
namespace App\Tools;
use App\PackageVersionTool;
use NeuronAI\Tools\Toolkits\AbstractToolkit;
use PDO;
final class ProjectToolkit extends AbstractToolkit
{
    public function __construct(private readonly PDO $pdo)
    {
    }
    #[\Override]
    public function guidelines(): ?string
    {
        return 'These tools answer questions about this project.'
            . ' For the exact version of one package, use the'
            . ' package version tool. To compare several packages'
            . ' with minimum versions, send them all in one call'
            . ' of the package check tool.';
    }
    #[\Override]
    public function provide(): array
    {
        return [
            new PackageVersionTool(),
            new CountMessagesTool($this->pdo),
            new CheckPackagesTool(),
        ];
    }
}
