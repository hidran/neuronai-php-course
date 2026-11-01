# Exercise: a narrow tool for the test configuration

Work in the `repo-auditor` project, after the git history tool is in place.

Write `App\Tools\TestConfigTool` (name `read_test_config`, no model properties). The project
directory and the evidence log come in through the constructor. The tool looks only at two
fixed names in the target: `phpunit.xml` and `phpunit.xml.dist`.

- No file: record `tests` as unavailable with the reason `no PHPUnit configuration`.
- A file over 16 KB: unavailable, `PHPUnit configuration is over 16 KB`.
- Invalid XML (`simplexml_load_string` returns false): unavailable, `invalid PHPUnit XML`.
- Otherwise record `tests` with status `ok` and the facts `file` (which name was found) and
  `suites` (the `name` attribute of every `testsuite`, at most ten).

Call it directly, without a model, on `library`, `app` and `legacy`. Then add it to the tools
of `AuditorAgent` with `setMaxRuns(1)` and explain in two sentences why this narrow tool is
safer evidence than asking the model to read `phpunit.xml` with `read_file`.

Acceptance: `library` gives `phpunit.xml.dist` with the suite `unit`, `app` gives
`phpunit.xml` with `feature`, `legacy` is unavailable with `no PHPUnit configuration`, and
no exception escapes. The runnable reference is `files/exercise-test-config.php <project>`.
