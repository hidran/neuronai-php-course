<?php

declare(strict_types=1);

namespace App\Tools;

use App\Dto\PackageFilter;
use Composer\InstalledVersions;
use NeuronAI\Tools\ObjectProperty;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;

class FindPackagesTool extends Tool
{
    protected string $name = 'find_packages';

    protected ?string $description = 'Lists the installed Composer'
        . ' packages of one vendor.';

    /**
     * Properties are the input arguments of the __invoke method.
     */
    protected function properties(): array
    {
        return [
            new ObjectProperty(
                name: 'filter',
                description: 'Which packages to list.',
                required: true,
                class: PackageFilter::class,
            ),
        ];
    }

    /**
     * Implementing the tool logic
     */
    public function __invoke(PackageFilter $filter): ToolOutput
    {
        $found = [];
        foreach (InstalledVersions::getInstalledPackages() as $name) {
            if (str_starts_with($name, "{$filter->vendor}/")) {
                $version = InstalledVersions::getPrettyVersion($name);
                $found[] = "{$name}: {$version}";
            }
        }
        $found = array_slice($found, 0, $filter->limit);
        return ToolOutput::text(implode("\n", $found) ?: 'None.');
    }
}
