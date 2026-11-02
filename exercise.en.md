# Exercise: review an assistant's proposal against vendor

Start from the Laravel project at the end of the lesson: the core skills are installed for your coding assistant, `requests/support-agent.txt` holds the request, and `tests/Unit/SupportAgentTest.php` passes with a fake provider.

1. Send the request in `requests/support-agent.txt` to the coding assistant you use, asking it to write its proposal to new files (for example `app/Neuron/Agents/MySupportAgent.php` and `tests/Unit/MySupportAgentTest.php`).
2. Review the proposal against `vendor/`, not against memory: for every NeuronAI method it calls, use Go to Definition and check the signature. List any named argument, method or hook that does not exist in the installed release (for example `make(threadId: ...)`, `setChatHistory()`, `resume()` or `approvalPolicy(array $inputs)`). Check the project's own conventions too: the agent is `final` and receives its provider in the constructor (`parent::__construct()`, then `setAiProvider()`), with no `provider()` override.
3. Run `php artisan test --filter=MySupportAgentTest`. If it fails, fix the code, not the test's intent: the test must still prove that `for()` returns a bound copy and that the definition stays unbound.
4. Fix `proposals/RefundTool.php` so that it compiles: `approvalPolicy()` takes no parameter, and the inputs of the call are read with `$this->getInput('order_id')` (add an `order_id` property). Check it with the same `php -r` command as the lesson.

Acceptance: your list from step 2 names each mismatch with the file and line of the installed signature; the new test passes with no paid model call; the `php -r` check prints nothing.

Without an assistant, do steps 2 to 4 on the lesson's own proposal. The lesson's offline check, `php verify-boost-skills.php <your project>`, ends with `BOOST SKILLS OK`.
