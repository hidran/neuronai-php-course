<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Mcp\PolicyDemo;
PolicyDemo::run($argv[1] ?? '--compare');
