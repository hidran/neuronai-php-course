# Exercise: verify different attachment facts

Use the completed media lesson project and its fictional PNG and PDF fixtures.

1. Build a UserMessage asking for the circle's label, not the triangle's color or label. Attach the PNG explicitly with ImageContent, BASE64 and PNG media type.
2. In a fresh thread, ask the PDF for its crate count, dock and review word. Attach FileContent using `content`, `sourceType`, `mediaType` and `filename`.
3. Inspect the ordered block classes without printing encoded payloads. Assert one text block followed by the correct media block.
4. Reject path traversal and a MIME mismatch before inference, using `MediaFixtures::path()`, and build both blocks from `MediaFixtures::encoded()`. Report provider failures rather than replacing missing evidence with a guess.
5. Compare the actual answers with the expected facts kept outside both prompts. The PNG's circle is labeled ORBIT-29. The PDF states seventeen crates, dock West-4 and review word saffron.

## Worked solution and checks

Copy `files/exercise-media-content.php` into `examples/exercise-media-content.php` in the completed project. Run in PhpStorm Terminal:

```text
php examples/exercise-media-content.php
php examples/exercise-media-content.php --live
```

The first run checks block order and local input refusals without loading credentials or making model requests. The live run adds two independent model calls, checks source facts and prints `Exercise complete.` only after both succeed. The live run builds the provider with `bootstrap.php` and `ProviderFactory`, so it uses the route configured in `.env`; keep the same media-capable route as the lesson (Anthropic). Never put a key in the code or display private configuration.

A well-formed message alone does not prove the attachment reached a capable model. The fact checks test that boundary. Live prose may vary; the required source facts must remain. Substring checks are a teaching diagnostic, not a general semantic evaluator: a real application should return structured fields and verify each field against its business requirements.
