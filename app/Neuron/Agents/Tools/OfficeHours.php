<?php

declare(strict_types=1);

namespace App\Neuron\Agents\Tools;

use NeuronAI\Tools\Tool;

class OfficeHours extends Tool
{
    protected string $name = 'OfficeHours';

    protected ?string $description = 'Published support hours. Read only.';

    /**
     * Properties are the input arguments of the __invoke method.
     */
    protected function properties(): array
    {
        return [];
    }

    /**
     * Implementing the tool logic
     */
    public function __invoke(): string
    {
        return 'Monday to Friday, 9:00 to 17:00 CET.';
    }
}
