# Exercise: keep the policy small and audited

Use the completed policy lesson project. The reviewed fixture is a stdio server, so no endpoint or key is needed for the discovery checks. One provider request performs the live south read.

1. Prove that an empty allowlist denies everything without constructing a connector. Count the connector factories before and after the call.
2. Select only `get_office_hours` and `list_locations` and verify the discovered set equals the allowed set, including order-independent comparison.
3. Add a second server that also exposes `get_office_hours`, then confirm that combining both lists raises `ToolException` before any inference. Resolve the conflict by keeping one capability per name.
4. Ask a fresh thread to list the locations first and then read the south office hours. Both capabilities must actually run.
5. Inspect the audited payloads: the south read carries only `location=south`, the location list carries no context, and no capability outside the allowlist appears.
6. Check the answer against the fixture facts: `10:00`, `18:00` and `Europe/Rome`. The question supplies none of them.

Copy `files/exercise-mcp-policy.php` into `examples/`, keep the lesson's policy helper, fixture, agent and demo installed, and run in PhpStorm Terminal:

```text
php examples/exercise-mcp-policy.php
```

Expected fixed labels:

```text
Deny-all built no connector: passed
Allowlist verified exactly: passed
Duplicate names rejected before inference: passed
South read and location list completed: passed
MCP policy exercise complete.
```

The final answer also appears. The exercise validates the audited names and argument shapes automatically; inspect `storage/mcp-policy.jsonl` to read the individual payloads. The exercise requires one provider request; the deny-all, allowlist and collision checks are local protocol work with no model involved. A duplicate name that reaches the model is not a harmless warning: it makes the chosen capability ambiguous, so the failure belongs before inference.
