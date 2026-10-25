# Exercise: let the evaluation pick the threshold

Work in the same cumulative project, with `src/Rag/RetrievalEval.php` and
`examples/60-retrieval-eval.php` as we wrote them, the three Tidepool guides
in `fixtures/docs` and the labeled questions in
`fixtures/quality/questions.php`.

1. Add a seventh labeled question, for example "How often does Tidepool
   sync?", with `setup.md` as its expected source.
2. Run the evaluation with the fixed threshold at 0.45 and at 0.60, next to
   plain retrieval and the 0.50 of the lesson. Compare hit rates and
   refusals: which answers does each value lose, and does the Excel question
   get refused?
3. Add one more setup that wraps the plain retrieval with
   `AdaptiveThresholdPostProcessor()`, the same way the lesson wrapped it
   with the fixed threshold: retrieval first, post processing second.
4. Run everything again and explain, in your own words, why the adaptive
   setup refuses nothing.

Acceptance: six answerable questions in every report, one report per setup
(plain, fixed 0.45, fixed 0.50, fixed 0.60, adaptive), and an adaptive setup
with zero refusals. Keep the new question in the questions file only if
your guides really answer it.

## Worked solution

Copy `files/exercise-retrieval-quality.php` into the project's
`examples/exercise-retrieval-quality.php`. It adds the sync question in
code, so the questions file stays untouched, and wraps the plain retrieval
with any post processor through one small anonymous class.

In the PhpStorm terminal, run:

```text
php examples/exercise-retrieval-quality.php
php examples/exercise-retrieval-quality.php --live
```

The first command uses `KeywordEmbeddings`: no network, no cost, and the
same numbers every time. Every setup finds the six expected guides, every
fixed threshold refuses the Excel question, and the adaptive setup refuses
nothing:

```text
== adaptive
  ...
  excel        expected nothing, got support.md
  sync         hit  expected setup.md, got setup.md (0.95)
hit rate 6/6, refused 0
Retrieval exercise complete.
```

The second command uses the embeddings provider from `.env`, as the lesson
does. The live scores are lower and closer together, so the thresholds
start to disagree: a higher floor refuses more but also loses real answers,
a lower one keeps the weak questions and more noise. The script throws if
the adaptive setup ever refuses a question.

Why the adaptive setup refuses nothing: it keeps the scores at or above the
median minus 0.6 times the median absolute deviation of the same result.
The best score is always at or above the median, so the top passage always
survives. A threshold relative to the batch can trim a weak tail, but only
an absolute floor can return nothing.
