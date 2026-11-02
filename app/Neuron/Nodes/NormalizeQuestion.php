<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\WorkflowState;

final class NormalizeQuestion extends Node
{
    /**
     * Implement the Node's logic
     */
    public function __invoke(StartEvent $event, WorkflowState $state): StopEvent
    {
        // ...

        return new StopEvent();
    }
}
