<?php
declare(strict_types=1);
namespace App\Knowledge;
use PDO;
final readonly class OrderDatabase
{
    public function __construct(private string $path)
    {
    }
    public function pdo(): PDO
    {
        $pdo = new PDO('sqlite:' . $this->path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
}
