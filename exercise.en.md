# Exercise: synchronize renamed and deleted documentation files

`reindexBySource()` replaces the chunks of every source present in the new
document batch, but it cannot know about a file that was renamed or deleted
from the folder. In this exercise you will turn source replacement into full
folder synchronization on an isolated copy of the documentation corpus.

Work in the cumulative lab with your existing `.env`. Create a working folder
at `storage/docs-exercise` with a copy of `fixtures/docs/*.md` in
`storage/docs-exercise/corpus`, a manifest file at
`storage/docs-exercise/indexed-sources.json`, and a dedicated
`FileVectorStore` in `storage/docs-exercise/index` with `topK: 50` and name
`'exercise-' . DocsAgent::EMBEDDINGS` so the lesson's index in `storage/docs`
is never modified.

Part one: write a `load(string $root)` helper that loads the Markdown corpus
with `FileDataLoader` and `MarkdownSectionSplitter` and normalizes each chunk's
source name relative to `$root`. Write a `sync()` function that loads the
current documents, reads the previous source list from the JSON manifest,
calls `$agent->reindexBySource($documents)`, computes the removed sources with
`array_diff($previous, $current)`, deletes each removed source from the
`FileVectorStore` using `FilterGroup::and(Filter::eq('sourceType', 'files'),
Filter::eq('sourceName', $name))`, writes the sorted current source list back
to the manifest, and returns the removed source names.

Part two: run `sync()` twice on the initial three files and verify that the
stored chunk counts per source (`billing.md x4, setup.md x4, support.md x3`,
11 chunks total) do not change on the second sync. Run the labeled questions
from `fixtures/docs-questions.php` through `resolveRetrieval()->retrieve()`
and check that every supported question ranks its expected file first.

Part three: rename `billing.md` to `invoices-and-plans.md` in the exercise
corpus. First call `$agent->reindexBySource(load($corpus))` directly and prove
that `billing.md` is still present alongside `invoices-and-plans.md` (15
chunks). Then call `sync()` and verify that `billing.md` is deleted and the
total returns to 11 chunks.

Part four: delete `support.md` from the exercise corpus, call `sync()`, and
verify that `support.md` is removed from the store, leaving 8 chunks across
`invoices-and-plans.md` and `setup.md`. Print `Docs sync exercise passed.`

The runnable reference is `examples/exercise-docs-sync.php`.
`examples/verify-docs-rag.php` checks the splitter rules, provenance, and
question fixtures offline with no API calls.
