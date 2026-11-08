# Exercise: one more reviewable change with your assistant

Work in the same cumulative project, with your own coding assistant (Claude Code, Codex, Cursor or another one).

Set it up the way the lesson did, and check each step:

1. Install the NeuronAI skills with the package's documented command, then run
   `php examples/61-skill-targets.php` and write down whether your assistant reads copies or links, and whether they
   match `vendor/`.
2. Keep the project rules file short; add one rule of your own that your application needs.
3. Connect the documentation MCP server (`https://docs.neuron-ai.dev/~gitbook/mcp`, HTTP transport) with your
   assistant's own instructions, and prove it with one documentation lookup that names the page it used.

Then ask for a small change. Users sometimes paste secrets into support questions, and a question goes to the model
and maybe into a log. Ask for a `GuardedQuestion` that wraps `SupportQuestion` and also rejects text that contains a
credential: a token that starts with `sk-` followed by at least eight letters, digits, hyphens or underscores, or an
upper-case name ending in `_KEY`, `_TOKEN` or `_SECRET` followed by `=`. Name the allowed files in your request, ask
for checks that need no model, and tell the assistant to verify unfamiliar methods in `vendor/`.

Review the result before you accept it: follow every call, go to the definition of anything you do not recognize, ask
the assistant for the source of anything unusual, and run `php examples/61-review-change.php` (after recording a fresh
baseline with `--baseline` before the request).

Acceptance: "How long do refunds take?" and "What is a skill key point for agents?" are accepted; a blank question,
"My key is sk-ant-abc123def456, why does it fail?" and "I set OPENAI_API_KEY=abc in my file, is that right?" are
rejected; the review lists only the files you allowed and `Credential references: 0` for the code itself (your test
data may need a reference: explain it in one sentence).

The runnable reference is `examples/exercise-question-guard.php` (offline, no key needed).
