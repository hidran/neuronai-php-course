<?php

declare(strict_types=1);

namespace App\Tools;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;

class FindPackagesTool extends Tool
{
    protected string $name = 'FindPackagesTool';

    protected ?string $description = 'Describe the purpose of the tool';

    /**
     * Properties are the input arguments of the __invoke method.
     */
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'input',
                type: PropertyType::STRING,
                description: 'The input argument of the tool',
                required: true,
            ),
        ];
    }

    /**
     * Implementing the tool logic
     */
    public function __invoke(string $input): ToolOutput
    {
        // ...

        return ToolOutput::text($input);
    }
}
