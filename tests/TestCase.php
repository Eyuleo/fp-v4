<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PDO;
use PDOException;

abstract class TestCase extends BaseTestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
    }

    protected function setupDatabase(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->runMigrations();
    }

    protected function runMigrations(): void
    {
        $migrationDir = __DIR__ . '/../migrations';
        $files = glob($migrationDir . '/*.sql');
        sort($files); // Ensure migrations run in order

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            $sql = $this->convertSqlForSqlite($sql);
            
            if (trim($sql) === '') {
                continue;
            }

            try {
                // Split by semicolon to execute multiple statements if any, 
                // though usually migration files here seem to be one statement per file or handled carefully.
                // SQLite exec can handle multiple statements in one string.
                $this->db->exec($sql);
            } catch (PDOException $e) {
                // Ignore errors about tables already existing or syntax we couldn't fix perfectly
                // But for now, let's throw to see what's wrong
                throw new PDOException("Migration failed: " . basename($file) . " - " . $e->getMessage() . "\nSQL: " . $sql);
            }
        }
    }

    protected function convertSqlForSqlite(string $sql): string
    {
        // Remove MySQL specific comments
        $sql = preg_replace('/^\s*#.*$/m', '', $sql);
        
        // Replace INT UNSIGNED AUTO_INCREMENT PRIMARY KEY with INTEGER PRIMARY KEY AUTOINCREMENT
        $sql = preg_replace('/INT\s+UNSIGNED\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        
        // Replace other INT UNSIGNED types
        $sql = preg_replace('/INT\s+UNSIGNED/i', 'INTEGER', $sql);
        
        // Replace ENUM(...) with TEXT
        $sql = preg_replace('/ENUM\([^)]+\)/i', 'TEXT', $sql);
        
        // Remove ON UPDATE CURRENT_TIMESTAMP
        $sql = preg_replace('/ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $sql);
        
        // Remove ENGINE=InnoDB ...
        $sql = preg_replace('/\)\s*ENGINE=[^;]+;/i', ');', $sql);
        
        // Remove MySQL keys/indexes defined inside CREATE TABLE
        // Matches "INDEX idx_name (col)," or "KEY idx_name (col),"
        $sql = preg_replace('/,\s*(?:INDEX|KEY)\s+\w+\s*\([^)]+\)/i', '', $sql);
        
        // Remove FULLTEXT indexes
        $sql = preg_replace('/,\s*FULLTEXT\s+\w+\s*\([^)]+\)/i', '', $sql);

        // Ignore ALTER TABLE ... MODIFY/CHANGE for SQLite
        if (stripos($sql, 'ALTER TABLE') !== false && (stripos($sql, 'MODIFY') !== false || stripos($sql, 'CHANGE') !== false)) {
            return '';
        }

        // Remove AFTER column syntax
        $sql = preg_replace('/\s+AFTER\s+\w+/i', '', $sql);

        // Handle multiple ADD COLUMN in one ALTER TABLE
        if (preg_match('/ALTER\s+TABLE\s+(\w+)\s+/i', $sql, $matches)) {
            $tableName = $matches[1];
            $sql = preg_replace('/,\s*ADD\s+COLUMN/i', "; ALTER TABLE $tableName ADD COLUMN", $sql);
            
            // Convert ADD INDEX to CREATE INDEX
            // Matches: ADD INDEX index_name (col)
            if (preg_match_all('/ADD\s+INDEX\s+(\w+)\s*\(([^)]+)\)/i', $sql, $indexMatches, PREG_SET_ORDER)) {
                foreach ($indexMatches as $match) {
                    $indexName = $match[1];
                    $columns = $match[2];
                    $sql = str_replace($match[0], "; CREATE INDEX $indexName ON $tableName ($columns)", $sql);
                }
            }
        }

        // Remove empty ALTER TABLE statements (leftover from replacements)
        // Matches ALTER TABLE table; or ALTER TABLE table (at end or before another statement)
        $sql = preg_replace('/ALTER\s+TABLE\s+\w+\s*(?:;|\s*$)/is', '', $sql);
        
        // Also clean up if there are multiple empty ALTER TABLEs
        $sql = preg_replace('/ALTER\s+TABLE\s+\w+\s*$/m', '', $sql);

        // Ignore ADD CONSTRAINT
        $sql = preg_replace('/,\s*ADD\s+CONSTRAINT\s+\w+\s+FOREIGN\s+KEY\s*\([^)]+\)\s+REFERENCES\s+\w+\s*\([^)]+\)/i', '', $sql);
        $sql = preg_replace('/ADD\s+CONSTRAINT\s+\w+\s+FOREIGN\s+KEY\s*\([^)]+\)\s+REFERENCES\s+\w+\s*\([^)]+\);/i', '', $sql);

        // Remove trailing comma if the last item was removed
        $sql = preg_replace('/,\s*\)/', ')', $sql);

        // Replace `id` INT ... AUTO_INCREMENT with INTEGER PRIMARY KEY AUTOINCREMENT if not covered above
        // Some files might use `id` INT AUTO_INCREMENT PRIMARY KEY
        $sql = preg_replace('/`?id`?\s+INT(?:EGER)?(?:\s+UNSIGNED)?\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'id INTEGER PRIMARY KEY AUTOINCREMENT', $sql);

        return $sql;
    }
}
