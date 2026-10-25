# Exercise: scope, limit and replace in a file store

Work in the same cumulative project, with your OpenAI key in `.env` (the
embeddings provider from the lesson: `text-embedding-3-small`, 1024
dimensions). Use a dedicated collection: a `FileVectorStore` in
`storage/vectors-exercise` named `catalog`, so the lesson's `support-demo`
store stays untouched. Delete `catalog.store` at the start of your script, so
every run begins with an empty collection.

Part one: build four documents in two source types. Two `faq` documents,
`refunds.md` and `shipping.md`, and two `changelog` documents,
`release-1.md` and `release-2.md`. Write one short sentence for each, with
one changelog entry that mentions store credit or refunds. Before the first
write, prove that constructing the store did not create `catalog.store`.
Embed all four with a single `embedDocuments` call, then add the same
embedded documents to the file store and to a `MemoryVectorStore`.

Part two: ask "How do I get my money back?" with a `SearchRequest` and
`topK: 2`. Print the source names from both stores and check that the two
rankings are identical and that `refunds.md` comes first.

Part three: ask a question about refund changes, with
`filters: Filter::eq('sourceType', 'changelog')` and `topK: 5`. Check that
exactly two documents come back and that every one of them is a changelog
entry. Explain, in your own words, why asking for five did not produce five.

Part four: read the first row of `catalog.store` with `fgets` and
`json_decode`, print its field names, and check that no `score` field is
stored. Then print the score of `refunds.md` for both questions and confirm
they differ.

Part five: the refund policy changed. Call `delete` on the file store with
`Filter::eq('sourceName', 'refunds.md')`, embed a new refunds sentence with
`embedDocument`, and add it with `addDocument`. Search with a `sourceName`
filter for `refunds.md` and check that exactly one row comes back, with the
new wording.

Acceptance: no file before the first write; identical rankings in memory and
on file with `refunds.md` first; two filtered results for a top K of five,
all `changelog`; stored fields `id, content, embedding, sourceType,
sourceName, metadata` and no `score`; two different scores for the same
passage; one replaced `refunds.md` row. The script prints
`Vector store exercise complete.` and runs again with the same result.

The runnable reference is `examples/exercise-vector-stores.php`.
`examples/verify-vector-stores.php` checks the lesson's claims offline, with
a keyword-counting embeddings class instead of the hosted model, so it needs
no key and no network.
