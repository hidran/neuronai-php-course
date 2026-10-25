# Practice: check a database analyst

1. Ask the agent for average October 2026 order value by country. Define order value as quantity times product price.
2. Inspect the query, including joins and date bounds. Run your own query in PhpStorm's Database window and compare.
3. Ask for the three products with the highest revenue across all dates. Include product names and sort by revenue.
4. Run `php examples/runtime-check-analyst.php`. Confirm direct writes and private table reads remain denied after both questions.

Expected October results: Germany (`DE`), one order, average 90.00. Italy (`IT`), three orders, average 156.67. Italy's unrounded average is 470 divided by 3. All-time product results: Monitor 600.00, Keyboard 270.00, Mouse 90.00. The September Monitor order belongs in the all-time answer and does not belong in the October answer.

A worked SQL answer is in `files/exercise-answer.sql`. Run it through the reader datasource, which has the same permissions as the agent. Accept different valid SQL and different final wording when the returned rows agree. Do not grant the reader more permissions to make a failed query succeed.
