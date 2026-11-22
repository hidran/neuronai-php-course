<?php

declare(strict_types=1);
$lab = $argv[1] ?? dirname(__DIR__);
require $lab . '/vendor/autoload.php';
// Price.php autoloads from src/Support/Price.php

use App\Support\Price;

$price = new Price(inputPerMillion: 3.0, outputPerMillion: 15.0);
$cost = $price->of(1_000_000, 100_000);
if (abs($cost - 4.5) > 0.0001) {
    throw new RuntimeException("cost {$cost}");
}
echo 'PRICE OK' . PHP_EOL;
