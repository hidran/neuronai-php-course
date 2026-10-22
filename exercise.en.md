# Verify a second office through the local server

Use the reviewed PHP stdio server. Keep stdout restricted to newline-delimited JSON-RPC responses. Do not add a debug echo or a second tool to make the checks pass.

1. Discover the exact allowlisted capability and verify its required location string before involving a model.
2. Invoke the south office directly through the connector. Check the exact returned location, hours `10:00-18:00` and timezone `Europe/Rome`.
3. Call an unknown location and a non-string location. Both must return a tool result marked as an error, rather than an invented schedule.
4. Restrict another connector to a misspelled tool name. It must discover an empty list. An application should reject that condition before inference.
5. Ask the agent about the south office in a fresh thread. Observe a real `get_office_hours` invocation and matching result, then verify the answer includes the actual fixture hours and timezone.

Copy `files/exercise-local-mcp.php` into the cumulative project's `examples/` folder and run:

```text
php examples/exercise-local-mcp.php
```

Expected evidence:

```text
Discovered: get_office_hours
Input: location (required string)
Tool class: NeuronAI\MCP\McpTool
Discovery ms: [one observation]
Discovery check complete.
Direct south invocation and error paths: passed
Misspelled allowlist discovered no tools: passed
[actual call ID, location input, matching result and grounded answer]
Tool stream complete.
South office grounded in an actual tool result: passed
Local MCP exercise complete.
```

The direct checks use the actual local child process without a model request. The last check uses the normal Anthropic-backed agent workflow and the existing streaming view. Explicit exceptions enforce checks independently of PHP assertion configuration. A tool's read-only annotation is metadata; the reviewed implementation and operating permissions determine what the child process can actually do.
