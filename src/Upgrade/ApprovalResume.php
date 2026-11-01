<?php
declare(strict_types=1);
namespace App\Upgrade;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;
// No resume() and no catch: a pause comes back as returned state,
// and the answer is staged with submitInputs() before run().
final readonly class ApprovalResume
{
    public function __construct(private Workflow $workflow)
    {
    }
    public function start(): WorkflowState
    {
        return $this->workflow->run();
    }
    /** @param array<string, mixed> $answer */
    public function answer(array $answer): WorkflowState
    {
        return $this->workflow->submitInputs($answer)->run();
    }
}
