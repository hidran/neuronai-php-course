<?php
declare(strict_types=1);
namespace App\Tools;
use Composer\InstalledVersions;
use NeuronAI\Tools\ArrayProperty;
use NeuronAI\Tools\ObjectProperty;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;
final class CheckPackagesTool extends Tool
{
    protected string $name = 'check_packages';
    protected ?string $description = 'Checks Composer packages of'
        . ' this project against minimum versions.';
    #[\Override]
    protected function properties(): array
    {
        $package = new ToolProperty(
            name: 'package',
            type: PropertyType::STRING,
            description: 'The package name, like vendor/name.',
            required: true,
        );
        $minimum = new ToolProperty(
            name: 'minimum',
            type: PropertyType::STRING,
            description: 'The lowest version we accept, like 5.6.',
        );
        $requirement = new ObjectProperty(
            name: 'requirement',
            properties: [$package, $minimum],
        );
        return [
            new ArrayProperty(
                name: 'requirements',
                description: 'The packages to check.',
                required: true,
                items: $requirement,
                maxItems: 5,
            ),
        ];
    }
    public function __invoke(array $requirements): ToolOutput
    {
        $lines = [];
        foreach ($requirements as $item) {
            $name = (string) ($item['package'] ?? '');
            $minimum = (string) ($item['minimum'] ?? '0');
            if (!InstalledVersions::isInstalled($name)) {
                $lines[] = "{$name}: not installed";
                continue;
            }
            $version = InstalledVersions::getPrettyVersion($name);
            $ok = version_compare(ltrim($version, 'v'), $minimum, '>=');
            $lines[] = "{$name}: {$version}, "
                . ($ok ? 'meets' : 'below') . " {$minimum}";
        }
        return ToolOutput::text(implode("\n", $lines));
    }
}
