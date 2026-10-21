<?php
declare(strict_types=1);
namespace App\Tools;
use App\PackageVersionTool;
use NeuronAI\Tools\Toolkits\AbstractToolkit;
use PDO;
class ProjectToolkit extends AbstractToolkit
{
    public function __construct(private PDO $pdo)
    {
    }
    public function guidelines(): ?string
    {
        return 'These tools answer questions about this project.'
            . ' For the exact version of one package, use'
            . ' get_package_version. To compare packages with'
            . ' minimum versions, send them all in one'
            . ' check_packages call.';
    }
    public function provide(): array
    {
        return [
            PackageVersionTool::make(),
            CountMessagesTool::make($this->pdo),
            CheckPackagesTool::make(),
        ];
    }
}
