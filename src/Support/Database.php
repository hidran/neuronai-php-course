<?php
declare(strict_types=1);
namespace App\Support;
use PDO;
final class Database
{
    private ?PDO $pdo = null;
    public function __construct(
        private readonly string $dsn,
        private readonly ?string $user = null,
        private readonly ?string $password = null,
    ) {
    }
    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->prepareDirectory();
            $this->pdo = new PDO(
                $this->dsn,
                $this->user,
                $this->password,
                $this->options(),
            );
        }
        return $this->pdo;
    }
    public function migrate(string $sqlFile): void
    {
        $this->pdo()->exec(file_get_contents($sqlFile));
    }
    private function options(): array
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if (str_starts_with($this->dsn, 'mysql:')) {
            $options[\Pdo\Mysql::ATTR_MULTI_STATEMENTS] = false;
        }
        return $options;
    }
    private function prepareDirectory(): void
    {
        if (!str_starts_with($this->dsn, 'sqlite:')) {
            return;
        }
        $dir = dirname(substr($this->dsn, strlen('sqlite:')));
        is_dir($dir) || mkdir($dir, recursive: true);
    }
}
