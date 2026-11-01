<?php
declare(strict_types=1);
namespace App\Neuron\Middleware;
use Illuminate\Support\Facades\Log;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\Middleware\WorkflowMiddleware;
use NeuronAI\Workflow\NodeInterface;
use NeuronAI\Workflow\WorkflowResources;
use NeuronAI\Workflow\WorkflowState;
final readonly class AuditMiddleware implements WorkflowMiddleware
{
    #[\Override]
    public function before(
        NodeInterface $node,
        Event $event,
        WorkflowState $state,
        WorkflowResources $resources,
    ): void {
        Log::info('audit: before ' . $node::class);
    }
    #[\Override]
    public function after(
        NodeInterface $node,
        Event $result,
        WorkflowState $state,
        WorkflowResources $resources,
    ): void {
        Log::info('audit: after ' . $node::class);
    }
}
