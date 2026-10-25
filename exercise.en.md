# Exercise: keep the policy small, framed and audited

Use the completed policy lesson project. The reviewed fixture is a stdio server, so no endpoint or key is needed for the local checks. One provider request performs the live south read.

1. Prove that an empty allowlist denies everything without building a connection: `select([])` returns no tools and the policy's `connections()` counter stays at zero.
2. Select `list_locations` and `get_office_hours` (in that order) and verify with `McpPolicy::assertExact()` that the discovered set equals the allowed set, whatever the order.
3. Combine the selected tools twice and confirm that `ToolRegistry` raises `ToolException` before any inference. A duplicate name makes the chosen capability ambiguous, so the failure belongs before the model sees the tools.
4. Call a framed read directly and check that its result carries the `<tool_result name="get_office_hours"` tag, the real hours and the provenance note.
5. Ask a fresh thread to list the locations first and then read the south office hours. Both capabilities must actually run.
6. Inspect the audit: every line names a tool from the allowlist, and every outcome is `ok`.

Copy `files/exercise-mcp-policy.php` into `examples/`, keep the lesson's policy, framed tool, agent and fixture installed, and run in the PhpStorm Terminal:

```text
php examples/exercise-mcp-policy.php
```

Expected fixed labels:

```text
Deny-all built no connection: passed
Allowlist verified exactly: passed
Duplicate names rejected before inference: passed
Tool results framed as data: passed
South read and location list audited: passed
MCP policy exercise complete.
```

The model's answer is printed before the last line; it should mention 10:00 to 18:00 and Europe/Rome, which the question does not supply. The first four checks are local protocol work with no model involved. Read `storage/mcp-policy.jsonl` to see the individual payloads: the south read carries only `location`, the location list carries no arguments.
