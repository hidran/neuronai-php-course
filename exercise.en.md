# Exercise: run every path of the diagnostic command

Start from the Laravel project at the end of the lesson: `app/Console/Commands/Diagnose.php` has the facade, chat, stream, structured and tool paths, and `AssistantAgent` comes from the container.

1. Run `php artisan diagnose stream`. Watch the answer arrive in pieces, then the class name printed after the loop. Explain why the last line is `NeuronAI\Agent\AgentState` even though the loop only printed text.
2. Run `php artisan diagnose structured`. Check that the two times come from the properties of a `SupportHours` object, not from the answer's prose.
3. Attach our `AuditMiddleware` to the bound copy on the tool path, after `addTool()`: `$agent->addMiddleware(ToolNode::class, new AuditMiddleware());` (import `NeuronAI\Agent\Nodes\ToolNode` and `App\Neuron\Middleware\AuditMiddleware`). Run `php artisan diagnose tool` and find the `audit: before` and `audit: after` lines in `storage/logs/laravel.log`.
4. Change `ToolNode::class` to a class name that does not exist in `vendor/neuron-core/neuron-ai/src/Agent/Nodes`, for example `App\StreamingNode`. Run the tool path again. Nothing fails, and nothing is logged: that is the silent monitoring gap the lesson warned about. Put `ToolNode::class` back.

Acceptance: the stream path ends with `NeuronAI\Agent\AgentState`; the structured path prints `From <opens> to <closes>`; the tool path prints `[office_hours] tool ran` and the log shows the audit lines for `ToolNode`; with an invented node class the log stays silent.

Without a provider key, run the offline check instead: `php verify-facade-alternative.php <your project>` drives every path with a fake provider and ends with `FACADE ALTERNATIVE OK`.
