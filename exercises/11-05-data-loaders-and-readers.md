# Exercise: prove what the loader extracted

Work in the same cumulative project, with the fixtures that `prepare.sh`
copies into `fixtures/rag-docs`.

Part one: load the whole `fixtures/rag-docs` directory with
`FileDataLoader`, registering `HtmlReader` for `html` and `PdfReader` for
`pdf`. For every returned document, print the source name, the character
count and the first fifty characters. Throw an exception if any chunk is
empty, so a silent extraction failure cannot pass as a successful run.
Print the total number of chunks and characters.

Part two: load `return-policy.md` again and replace the default splitter
with `SentenceTextSplitter(maxWords: 25)`. Print the word count of every
chunk and throw if a chunk exceeds the limit. Explain, in your own words,
why changing the splitter changes what retrieval can return, even though
the file and the reader are unchanged.

Then load a short article with `StringDataLoader`, set the source type to
`cms` and the source name to a stable article identifier, and print the
resulting document.

Acceptance: four source files read from the directory, no empty chunk,
the HTML article text without `<h1>` markup, the PDF text layer extracted,
every sentence chunk at or below 25 words, and the string document keeping
its custom source type and name. The runnable reference is
`examples/exercise-loaders.php`; `examples/verify-loaders.php` checks the
same invariants without reading the printed output.
