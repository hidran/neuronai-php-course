<?php
declare(strict_types=1);
// This entry point runs only the deterministic PHPUnit suite.
$root = dirname(__DIR__);
passthru(PHP_BINARY . ' ' . escapeshellarg(
    $root . '/vendor/phpunit/phpunit/phpunit'
) . ' --configuration ' . escapeshellarg(
    $root . '/phpunit.xml'
), $status);
if ($status === 0) {
    echo "Deterministic suite complete.\n";
}
exit($status);
