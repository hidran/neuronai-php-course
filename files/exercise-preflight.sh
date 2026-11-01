#!/usr/bin/env bash
# Reference solution of the exercise: builds fixtures/cli-tool in a temporary copy of the project and runs the
# preflight before and after git init. Usage: exercise-preflight.sh <repo-auditor project>
set -euo pipefail
project="${1:?repo-auditor project}"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
cp -R "$project/." "$work/"
target="$work/fixtures/cli-tool"
mkdir -p "$target/src" "$target/bin"
cat > "$target/composer.json" <<'JSON'
{
    "name": "acme/word-count",
    "require": {
        "php": "^8.3"
    },
    "bin": ["bin/word-count"],
    "autoload": {
        "psr-4": {
            "Acme\\WordCount\\": "src/"
        }
    }
}
JSON
printf '<?php\nnamespace Acme\\WordCount;\nfinal class Counter\n{\n    public function count(string $text): int\n    {\n        return str_word_count($text);\n    }\n}\n' > "$target/src/Counter.php"
printf '#!/usr/bin/env php\n<?php\necho "word count\\n";\n' > "$target/bin/word-count"
before="$(cd "$work" && php auditor.php fixtures/cli-tool)"
GIT_CONFIG_GLOBAL=/dev/null git -C "$target" init -q
GIT_CONFIG_GLOBAL=/dev/null git -C "$target" -c user.name=Exercise -c user.email=exercise@example.test \
  -c commit.gpgsign=false -c core.hooksPath=/dev/null add -A
GIT_CONFIG_GLOBAL=/dev/null git -C "$target" -c user.name=Exercise -c user.email=exercise@example.test \
  -c commit.gpgsign=false -c core.hooksPath=/dev/null commit -q -m "Create the word counter"
after="$(cd "$work" && php auditor.php fixtures/cli-tool)"
printf '%s\n---\n%s\n' "$before" "$after"
grep -qx 'Git history: unavailable (no git metadata)' <<<"$before"
grep -qx 'Git history: found' <<<"$after"
[[ "$(grep -v '^Git history' <<<"$before")" == "$(grep -v '^Git history' <<<"$after")" ]]
echo "EXERCISE OK"
