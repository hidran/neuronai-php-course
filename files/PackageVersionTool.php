<?php
declare(strict_types=1);
namespace App;
use Composer\InstalledVersions;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
class PackageVersionTool extends Tool
{
    protected string $name = 'get_package_version';
    protected ?string $description = 'Returns the version of a '
        . 'Composer package installed in this project.';
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'package',
                type: PropertyType::STRING,
                description: 'The package name, like vendor/name.',
                required: true,
            ),
        ];
    }
    public function __invoke(string $package): string
    {
        echo "Tool called: {$this->name} with {$package}" . PHP_EOL;
        if (!InstalledVersions::isInstalled($package)) {
            return "{$package} is not installed.";
        }
        $version = InstalledVersions::getPrettyVersion($package);
        return "{$package} is at version {$version}.";
    }
}
