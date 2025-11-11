<?php

/**
 * Database Migration System
 *
 * Runs SQL migrations sequentially and tracks applied migrations
 * Usage: php cli/migrate.php [up|down|status]
 */

// Load database configuration
if (! function_exists('getDatabaseConnection')) {
    require_once __DIR__ . '/../config/database.php';
}

class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct(PDO $pdo, string $migrationsPath)
    {
        $this->pdo            = $pdo;
        $this->migrationsPath = $migrationsPath;
        $this->ensureMigrationsTable();
    }

    /**
     * Create migrations tracking table if it doesn't exist
     */
    private function ensureMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) UNIQUE NOT NULL,
            batch INT UNSIGNED NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB";

        $this->pdo->exec($sql);
    }

    /**
     * Get all migration files from the migrations directory
     */
    private function getMigrationFiles(): array
    {
        if (! is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);

        return array_map(function ($file) {
            return basename($file);
        }, $files);
    }

    /**
     * Get applied migrations from database
     */
    private function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query(
            "SELECT migration FROM {$this->migrationsTable} ORDER BY id"
        );

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get the next batch number
     */
    private function getNextBatch(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COALESCE(MAX(batch), 0) + 1 as next_batch FROM {$this->migrationsTable}"
        );

        return (int) $stmt->fetchColumn();
    }

    /**
     * Run pending migrations
     */
    public function migrate(): void
    {
        $allMigrations     = $this->getMigrationFiles();
        $appliedMigrations = $this->getAppliedMigrations();
        $pendingMigrations = array_diff($allMigrations, $appliedMigrations);

        if (empty($pendingMigrations)) {
            echo "No pending migrations.\n";
            return;
        }

        $batch = $this->getNextBatch();

        echo "Running " . count($pendingMigrations) . " migration(s)...\n\n";

        foreach ($pendingMigrations as $migration) {
            $this->runMigration($migration, $batch);
        }

        echo "\nMigrations completed successfully!\n";
    }

    /**
     * Run a single migration file
     */
    private function runMigration(string $migration, int $batch): void
    {
        $filePath = $this->migrationsPath . '/' . $migration;

        if (! file_exists($filePath)) {
            throw new Exception("Migration file not found: {$filePath}");
        }

        echo "Migrating: {$migration}...";

        try {
            // Read and execute SQL file
            $sql = file_get_contents($filePath);
            $this->pdo->exec($sql);

            // Record migration
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)"
            );
            $stmt->execute([$migration, $batch]);

            echo " DONE\n";
        } catch (Exception $e) {
            echo " FAILED\n";
            throw new Exception("Migration failed: {$migration}\nError: " . $e->getMessage());
        }
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback(): void
    {
        $stmt = $this->pdo->query(
            "SELECT MAX(batch) as last_batch FROM {$this->migrationsTable}"
        );
        $lastBatch = $stmt->fetchColumn();

        if (! $lastBatch) {
            echo "Nothing to rollback.\n";
            return;
        }

        $stmt = $this->pdo->prepare(
            "SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id DESC"
        );
        $stmt->execute([$lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "Rolling back batch {$lastBatch} (" . count($migrations) . " migration(s))...\n\n";

        foreach ($migrations as $migration) {
            $this->rollbackMigration($migration);
        }

        echo "\nRollback completed successfully!\n";
    }

    /**
     * Rollback a single migration
     */
    private function rollbackMigration(string $migration): void
    {
        echo "Rolling back: {$migration}...";

        try {
            // For rollback, we'll drop the table created by the migration
            // This is a simple approach for development
            $tableName = $this->extractTableName($migration);

            if ($tableName) {
                $this->pdo->exec("DROP TABLE IF EXISTS {$tableName}");
            }

            // Remove migration record
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->migrationsTable} WHERE migration = ?"
            );
            $stmt->execute([$migration]);

            echo " DONE\n";
        } catch (Exception $e) {
            echo " FAILED\n";
            throw new Exception("Rollback failed: {$migration}\nError: " . $e->getMessage());
        }
    }

    /**
     * Extract table name from migration filename
     */
    private function extractTableName(string $migration): ?string
    {
        // Pattern: XXX_create_TABLENAME_table.sql
        if (preg_match('/\d+_create_(.+)_table\.sql/', $migration, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Show migration status
     */
    public function status(): void
    {
        $allMigrations     = $this->getMigrationFiles();
        $appliedMigrations = $this->getAppliedMigrations();

        echo "Migration Status:\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-60s %s\n", "Migration", "Status");
        echo str_repeat('-', 80) . "\n";

        foreach ($allMigrations as $migration) {
            $status = in_array($migration, $appliedMigrations) ? 'Applied' : 'Pending';
            printf("%-60s %s\n", $migration, $status);
        }

        echo str_repeat('-', 80) . "\n";
        echo "Total: " . count($allMigrations) . " migrations\n";
        echo "Applied: " . count($appliedMigrations) . " migrations\n";
        echo "Pending: " . (count($allMigrations) - count($appliedMigrations)) . " migrations\n";
    }
}

// Main execution
try {
    $pdo            = getDatabaseConnection();
    $migrationsPath = __DIR__ . '/../migrations';

    $runner = new MigrationRunner($pdo, $migrationsPath);

    $command = $argv[1] ?? 'up';

    switch ($command) {
        case 'up':
        case 'migrate':
            $runner->migrate();
            break;

        case 'down':
        case 'rollback':
            $runner->rollback();
            break;

        case 'status':
            $runner->status();
            break;

        default:
            echo "Usage: php migrate.php [up|down|status]\n";
            echo "  up/migrate  - Run pending migrations\n";
            echo "  down/rollback - Rollback last batch of migrations\n";
            echo "  status      - Show migration status\n";
            exit(1);
    }
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    exit(1);
}
