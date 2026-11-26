<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Idempotent schema migrator for SQLite and MySQL.
 */
final class Migrator
{
    private Database $db;
    private string $rootDir;

    public function __construct(Database $db, string $rootDir)
    {
        $this->db = $db;
        $this->rootDir = $rootDir;
    }

    public function ensureSchema(): void
    {
        if ($this->tableExists('users')) {
            return;
        }

        $driver = $this->db->driver();
        $schemaFile = $this->rootDir . '/database/schema.' . $driver . '.sql';
        if (!is_file($schemaFile)) {
            throw new RuntimeException('Schema-bestand niet gevonden: ' . $schemaFile);
        }

        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            throw new RuntimeException('Kan schema-bestand niet lezen: ' . $schemaFile);
        }

        $statements = $this->splitStatements($sql);
        foreach ($statements as $statement) {
            $this->db->pdo()->exec($statement);
        }
    }

    private function tableExists(string $tableName): bool
    {
        if ($this->db->driver() === 'sqlite') {
            $row = $this->db->fetchOne(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$tableName]
            );
            return $row !== null;
        }

        $row = $this->db->fetchOne('SHOW TABLES LIKE ?', [$tableName]);
        return $row !== null;
    }

    /**
     * @return array<int, string>
     */
    private function splitStatements(string $sql): array
    {
        $lines = preg_split('/\R/', $sql) ?: [];
        $filtered = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            $filtered[] = $line;
        }

        $cleanSql = implode("\n", $filtered);
        $parts = explode(';', $cleanSql);
        $statements = [];

        foreach ($parts as $part) {
            $stmt = trim($part);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
        }

        return $statements;
    }
}
