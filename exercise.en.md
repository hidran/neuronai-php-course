# Exercise: protect a second tool result

Add a deterministic calculator tool named `add_amounts`. Register it
alongside the weather tool with its stub client. Script one
ToolCallMessage with two calls using distinct identifiers, then a fixed
AssistantMessage. No provider credentials, environment loader or live
evaluation belongs in this exercise.

1. Inject a fresh ArrayObject into the calculator tool and record both
   operands. Return their sum as a string. Give its two `ToolProperty`
   entries named arguments and a real description.
2. Assert both invocations (weather coordinates and calculator operands),
   the call count and both tool result values, each keyed by its call
   identifier.
3. Inspect the second request's ToolResultMessage. Do not infer successful
   execution merely from the canned final answer.
4. Swap the recorded operands temporarily and show why the recording
   assertion still matters, even when addition returns the same sum.
5. Restore the solution, run the deterministic suite with network denied,
   then repeat it fifty times.

Acceptance: two independent recorders, no global history, no persistent
stores, no live model, exact result to call association, and a failing
assertion when operand order is deliberately reversed. The solution is in
`exercises/ExerciseTest.php`, outside the default suite: run it with
`vendor/bin/phpunit exercises/ExerciseTest.php` (1 test, 5 assertions).
The example tool is intentionally local to that test.
