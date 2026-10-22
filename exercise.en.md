# Correlate repeated tool invocations

Extend the tool progress consumer with checks that distinguish one invocation from another, keep raw tool payloads out of the user progress display and report interrupted answers honestly.

Use the existing `ServerConfigurationTool`, `ServerAgent`, `StreamProgress` and `ToolStreamConsumer`. No new execution interface is needed.

1. Build a deterministic stream with two calls of the same capability and different call IDs. Return their results in reverse order. Check that both invocations complete under their own IDs.
2. Give the calls an unknown internal tool name and obvious sentinel values in their inputs and results. The user display must show the generic fallback twice. It must reveal neither internal names nor payloads.
3. Include a partial `ToolArgumentChunk` and an unrelated object with a `content` property. Neither may become answer text or trigger any execution.
4. Add introductory text before the tool round and final answer text after it. The final state represents the last answer; it does not contain every introductory fragment from previous inferences.
5. Simulate a provider failure after half a sentence. Keep the partial text visible, report that the answer did not finish and return no completed state. Print no success label or raw exception details.
6. Reject a result whose ID has no matching pending invocation.
7. Make one real request asking for both training and backup addresses. Require two distinct call IDs for the same capability, check the lifecycle against the execution state's actual tool run count and verify both fixture addresses in the final answer.

Use explicit exceptions for failed checks rather than PHP's configurable `assert()`. Tool arguments and result payloads can be safely synthetic in the deterministic cases. Never use real credentials as test payloads.

Copy `files/exercise-tool-progress.php` to the project's `examples/` folder and run:

```text
php examples/exercise-tool-progress.php
```

Expected stable evidence, with live progress and generated answer between the final two checks:

```text
Repeated name and reverse results: passed
Unknown names and raw payload policy: passed
Partial failure without false success: passed
Unmatched result rejected: passed
[allowlisted progress and an answer using both fixture addresses]
Tool stream complete.
Two live invocations have distinct IDs: passed
Tool progress exercise complete.
```

The deterministic checks use no provider requests. The final check uses the actual Anthropic-backed workflow and ordinary registered tool. This policy controls progress output. It does not redact arbitrary generated answer text or replace tool authorization and data-access rules.
