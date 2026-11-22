# Exercise: review a package before you adopt it

Work in the same cumulative project. Pick two packages from the NeuronAI ecosystem: Maestro (its manifest is in
`../maestro-sandbox/vendor/neuron-core/maestro/composer.json`) and one more, for example `inspector-apm/inspector-php`
from our own `vendor/`, or any extension you found on Neuron Hub (download only its `composer.json`).

Write `examples/exercise-compatibility.php`. It receives one or more manifest paths on the command line and prints,
for each package: its name, its license, its `neuron-core/neuron-ai` constraint with the major versions that
constraint allows, and a verdict against the release our application has installed (read it with
`Composer\InstalledVersions::getPrettyVersion('neuron-core/neuron-ai')`). A package that does not require the core
directly gets the line "not required directly; check its dependencies".

Keep it offline: no network call, no model call.

Acceptance: Maestro is reported with constraint `^3.0`, major 3, and a "not a drop-in" verdict for our fourth major;
`inspector-apm/inspector-php` is reported as not requiring the core directly; the script ends with
`Compatibility review complete.`

Then answer in a few sentences, for the extension you chose: who maintains it, which tools it exposes, which of them
can write or delete something, and which tests you would add before putting it behind an allowlist in our application.

The runnable reference is `examples/exercise-compatibility.php`.
