<?php
declare(strict_types=1);
require __DIR__ . '/FactStore.php';
use App\Support\FactStore;
$dir = sys_get_temp_dir() . '/neuron-facts-' . bin2hex(random_bytes(3));
$store = new FactStore($dir);
if ($store->recall('valerio') !== []) {
    throw new RuntimeException('new user should have no facts');
}
$store->remember('valerio', 'likes PHP');
$store->remember('valerio', 'lives in Rome');
if ($store->recall('valerio') !== ['likes PHP', 'lives in Rome']) {
    throw new RuntimeException('facts not persisted in order');
}
try {
    $store->recall('../etc/passwd');
    throw new RuntimeException('path traversal accepted');
} catch (InvalidArgumentException) {
    echo 'bad user rejected' . PHP_EOL;
}
echo 'FACTS OK' . PHP_EOL;
