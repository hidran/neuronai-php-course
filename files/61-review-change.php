<?php
declare(strict_types=1);
// Review of an assistant's change: which application files are new or
// changed since the baseline, are they the allowed ones, and does any
// of them mention a credential? "--baseline" records the current state.
$root = dirname(__DIR__);
$baseline = $root . '/storage/review-baseline.json';
$allowed = ['src/Support/SupportQuestion.php',
    'src/Support/SupportAsk.php', 'examples/61-support-ask.php',
    'tests/SupportAskTest.php'];
$hashes = [];
foreach (['src', 'examples', 'tests'] as $dir) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator("{$root}/{$dir}",
            FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $path = substr($file->getPathname(), strlen($root) + 1);
        $hashes[$path] = sha1_file($file->getPathname());
    }
}
ksort($hashes);
if (($argv[1] ?? '') === '--baseline') {
    file_put_contents($baseline,
        json_encode($hashes, JSON_PRETTY_PRINT));
    echo 'Baseline recorded: ' . count($hashes) . ' files' . PHP_EOL;
    exit(0);
}
$before = json_decode((string) file_get_contents($baseline), true);
$changed = array_keys(array_diff_assoc($hashes, $before));
$secrets = 0;
foreach ($changed as $path) {
    $verdict = in_array($path, $allowed, true)
        ? 'allowed' : 'NOT ALLOWED';
    echo "Changed: {$path} ({$verdict})" . PHP_EOL;
    $text = (string) file_get_contents("{$root}/{$path}");
    $secrets += preg_match_all(
        '/_KEY\b|api_key|sk-[A-Za-z0-9]/', $text);
}
$gone = array_diff(array_keys($before), array_keys($hashes));
foreach ($gone as $path) {
    echo "Deleted: {$path} (NOT ALLOWED)" . PHP_EOL;
}
echo 'Credential references: ' . $secrets . PHP_EOL;
echo 'Review complete: ' . count($changed) . ' files' . PHP_EOL;
