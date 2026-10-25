# Exercise: counts are records too

Work in the same cumulative project. The orders table the lesson used lives in
`storage/knowledge/orders.sqlite` (open it with `new App\Knowledge\OrderDatabase($path)->pdo()`).

Part one: route three new requests with the authorities of our decision table.

- "What warranty does the Cedar Desk Lamp have?"
- "How many refunds did I get in October 2026?"
- "Always answer in our support team's voice."

For each one, write down its expected authority (short stable text, document,
record or learned behavior) and print the mechanism the table assigns to it.

Part two: the refund question is a count, so it belongs behind a tool, never
behind vector search. Write a `RefundCountTool` with one `month` property
(`YYYY-MM`). The application passes the signed-in customer to the constructor;
the model passes only the month. The tool runs one exact `COUNT(*)` query for
that customer and month and returns the number. It rejects a month such as
`October` with `ToolOutput::error()`.

Check it without a model: customer `C-204` must get 2 refunded orders in
`2026-10` and customer `C-118` must get 1 (their September refund does not
count). Then give the tool to an agent bound to `C-204`, ask "How many refunds
did I get this month?", and confirm from `getToolRuns('refund_count')` that the
model called the tool instead of guessing.

Explain in two sentences why indexing the orders table in a vector store would
give an approximate answer to this question.

Acceptance: the three routes print `direct context`, `tools` and `fine tuning`;
the counts are exactly 2 and 1; the invalid month is rejected; the model run
calls `refund_count` at least once. The runnable reference is
`examples/exercise-knowledge.php` (`php examples/exercise-knowledge.php ask`
adds the model call).
