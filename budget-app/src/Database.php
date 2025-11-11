<?php
namespace BudgetApp;

class Database {
    private \PDO $pdo;
    private string $dbPath;

    public function __construct(string $dbPath) {
        $this->dbPath = $dbPath;
        $this->connect();
        $this->initializeDatabase();
    }

    private function connect(): void {
        try {
            $this->pdo = new \PDO("sqlite:{$this->dbPath}");
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            // Enable foreign keys in SQLite
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function initializeDatabase(): void {
        // Check if tables exist
        $tables = $this->query("SELECT name FROM sqlite_master WHERE type='table'");

        if (empty($tables)) {
            $this->initializeSchema();
        }
    }

    private function initializeSchema(): void {
        $schemaPath = dirname(__DIR__) . '/database/schema.sql';

        if (!file_exists($schemaPath)) {
            throw new \Exception("Schema file not found: {$schemaPath}");
        }

        $schema = file_get_contents($schemaPath);
        $this->pdo->exec($schema);
    }

    public function query(string $sql, array $params = []): array {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception("Query error: " . $e->getMessage());
        }
    }

    public function queryOne(string $sql, array $params = []): ?array {
        $results = $this->query($sql, $params);
        return $results[0] ?? null;
    }

    public function execute(string $sql, array $params = []): int {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception("Execute error: " . $e->getMessage());
        }
    }

    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->execute($sql, array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int {
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));

        $sql = "UPDATE {$table} SET {$set} WHERE {$whereClause}";
        $params = array_merge(array_values($data), array_values($where));

        return $this->execute($sql, $params);
    }

    public function delete(string $table, array $where): int {
        $whereClause = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";

        return $this->execute($sql, array_values($where));
    }

    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }

    public function commit(): void {
        $this->pdo->commit();
    }

    public function rollback(): void {
        $this->pdo->rollBack();
    }

    public function getPdo(): \PDO {
        return $this->pdo;
    }

    public function close(): void {
        $this->pdo = null;
    }
}
