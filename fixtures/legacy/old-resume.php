<?php
// An older workflow snippet, kept as a fixture: never run it.
declare(strict_types=1);
use NeuronAI\Workflow\WorkflowInterrupt;
try {
    $state = $workflow->run();
} catch (WorkflowInterrupt $interrupt) {
    $request = $interrupt->getRequest();
    $request->approve();
    $state = $workflow->resume($request);
}
