# Exercise: keep the protocol boundary visible

Use the completed HTTP lesson project and its loopback teaching endpoint. Start the endpoint first with `php examples/http-server.php start`; every step below talks to that reviewed fixture and no other service.

1. Discover through `http://127.0.0.1:48392/mcp-events`, the SSE-framed route, instead of the plain `/mcp` endpoint. Assert exactly one tool named `get_office_hours` with a required string `location`.
2. Record the session files before discovery, then prove the fixture created exactly one new session for that connection.
3. Delete that session file and call `south` on the same connector. The transport must recover from the expired identifier rather than fail, and the result must contain `10:00-18:00`.
4. Attempt discovery on `/mcp-bad-schema` and require a `ToolException`: the capability exists but cannot become a NeuronAI tool. Attempt `/not-a-protocol-endpoint` and require an `McpException`: the transport, not the schema, is wrong.
5. Confirm that `'async' => true` alone does not select a legacy server. The same capability must be rejected as a legacy SSE connection failure, not treated as a concurrency switch.
6. Run one real inference through the SSE-framed endpoint and check that `get_office_hours` actually ran, and that the answer contains the south hours and `Europe/Rome`. The question itself supplies neither value.

Copy `files/exercise-http-mcp.php` into `examples/`, keep the lesson's completed connector and agent installed, and run in PhpStorm Terminal:

```text
php examples/exercise-http-mcp.php
```

Expected fixed labels:

```text
SSE response and expired-session recovery: passed
Transport and schema failures distinguished: passed
Legacy selector is not a concurrency flag: passed
South office over SSE-framed Streamable HTTP: passed
HTTP MCP exercise complete.
```

The measured discovery time and the invocation record also appear. The exercise requires one provider request; the three failure checks and the session recovery are local protocol work with no model involved. Session identifiers belong to one transport conversation, so they are never copied into application code or reused across runs. A deleted teaching session is a deliberate fixture state, not a production recovery policy.
