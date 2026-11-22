# Exercise: chunk a FAQ page without breaking its answers

Work in the same cumulative project. `prepare.sh` copies a small FAQ page to
`fixtures/rag-chunks/faq.md`: a title and four entries, each a `Q:` line
followed by an `A:` line. Two answers start with "After that", which only
makes sense next to the question and the first half of the answer.

Part one: load `faq.md` with `FileDataLoader` three times, each with a
different splitter:

- `DelimiterTextSplitter(maxLength: 80, separator: '.')`
- `SentenceTextSplitter(maxWords: 20, minWords: 5)`
- your own `FaqSplitter`

Write `FaqSplitter` by extending `AbstractSplitter`. It returns one chunk per
question and answer pair, skips the page title, copies source type, source
name and metadata from the original document, and adds a `question` metadata
field holding the `Q:` line.

Part two: measure each configuration with plain P H P, no embeddings. Print
the number of chunks, the largest chunk in characters and in words, and how
many pairs are intact: a pair is intact when a single chunk contains the
whole question and the whole answer (compare with whitespace normalized,
because the sentence splitter joins words with single spaces).

Part three: turn your expectations into checks that stop the script when
they fail: four pairs in the page, every pair intact with `FaqSplitter`, at
least one pair broken by the delimiter configuration, every `FaqSplitter`
chunk with source type `files`, the file path as source name and a
`question` field, and no chunks for an empty document.

Then answer in a few sentences: which configuration would you index for this
page, and why is zero overlap a sensible choice here, unlike the returns
policy we chunked in the lesson?

Acceptance: delimiter and sentence configurations report fewer than four
intact pairs (the reference run finds zero for both), `FaqSplitter` reports
4 chunks and 4/4 intact pairs, every check prints `ok`, and the script ends
with `Chunking exercise complete.` The runnable reference is
`examples/exercise-chunking.php`.
