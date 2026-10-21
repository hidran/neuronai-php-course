<?php
declare(strict_types=1);
$port = getenv('ANALYST_PORT') ?: '13316';
$dsn = 'mysql:host=127.0.0.1;port=' . $port
    . ';dbname=neuron_shop;charset=utf8mb4';
return new PDO($dsn, 'analyst_ro', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    Pdo\Mysql::ATTR_MULTI_STATEMENTS => false,
]);
