<?php
declare(strict_types=1);
use App\Config\Settings;
use Dotenv\Dotenv;
Dotenv::createImmutable(dirname(__DIR__))->load();
return Settings::fromEnv($_ENV);
