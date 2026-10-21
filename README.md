# Requesting structured output

Source and exercise for **Agentic AI in PHP with NeuronAI**.

PHP 8.5 or later and Composer are required. Run `composer install` in this folder.

The `files/` directory preserves the lesson source. Place the files as follows:

- Copy `files/Person.php` to `src/Output/Person.php`.
- Copy `files/34-request-structured.php` to `examples/34-request-structured.php` and run `php examples/34-request-structured.php`.
- Copy `files/PersonExtractor.php` to `src/Agents/PersonExtractor.php`.

Copy `.env.example` to `.env` locally and supply your own Anthropic key and model. Keep `.env` out of Git.

The first run uses the per-call output contract. For the default-contract version, replace `src/Agents/PersonExtractor.php` with `files/PersonExtractor.default.php`, and `examples/34-request-structured.php` with `files/34-request-structured.default.php`. Keep the destination filenames unchanged. Run the example again.

For the exercise, copy `files/exercise-request-structured.php` to `examples/exercise-request-structured.php` after applying the default-contract agent, then run `php examples/exercise-request-structured.php`.

Follow `exercise.en.md` for the practice and worked solution.

[Browse this lesson](https://github.com/hidran/neuronai-php-course/tree/lesson/06-03-requesting-structured-output)
