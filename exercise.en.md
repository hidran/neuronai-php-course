# Exercise: generate, inspect, prove

Start from the Laravel project at the end of the lesson: `AssistantAgent` as generated, `OfficeHours` implemented, `NormalizeQuestion` with repaired imports.

1. Generate a tool with `php artisan neuron:tool SupportEmail`. Give it a concrete description, an empty `properties()` array, and an `__invoke(): string` that returns `support@example.com`. Remove the imports it no longer uses.
2. Call it directly: `php artisan tinker --execute "echo (new App\Neuron\Agents\Tools\SupportEmail)();"`.
3. Generate a second node, `php artisan neuron:node TrimQuestion`. Before repairing it, run it in a workflow bound to an identifier, the same tinker one-liner as in the lesson with `TrimQuestion`, and copy the error message. Repair the two imports and run it again.
4. Generate a workflow with `php artisan neuron:workflow SupportFlow` and open it. Which namespace did the generator choose, and what is still missing before it can run?

Acceptance: the tool prints the address without any model call; the unrepaired node fails with "First parameter of __invoke method must be a type that implements NeuronAI\Workflow\Events\Event", the repaired one prints `NeuronAI\Workflow\WorkflowState`; the workflow is in `App\Neuron\Workflows` and still needs real nodes and a workflow identifier.
