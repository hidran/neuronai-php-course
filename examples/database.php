<?php
declare(strict_types=1);
use App\Config\StorageSettings;
use App\Support\Database;
$storage = StorageSettings::fromEnv($_ENV, dirname(__DIR__));
return new Database($storage->dsn, $storage->user, $storage->password)
    ->pdo();
