<?php
declare(strict_types=1);
namespace App;
use Composer\InstalledVersions;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
final class PackageVersionTool extends Tool
{
    protected string $name = 'get_package_version';
    protected ?string $description = 'Returns the installed version'
        . ' of a Composer package of this project.';
    #[\Override]
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'package',
                type: PropertyType::STRING,
                description: 'Package name as vendor/name, lowercase.',
                required: true,
            ),
        ];
    }
    public function __invoke(string $package): string
    {
        if (!InstalledVersions::isInstalled($package)) {
            return "{$package} is not installed.";
        }
        $version = InstalledVersions::getPrettyVersion($package);
        return "{$package} is at version {$version}.";
    }
}
