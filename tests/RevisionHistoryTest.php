<?php

/**
 * Revision History Test
 *
 * Tests the revision history tracking implementation
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';

class RevisionHistoryTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private OrderRepository $orderRepository;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
        $this->orderRepository = new OrderRepository($this->db);
    }

    public function run(): void
    {
        echo "Running Revision History Tests...\n\n";

        $this->testRevisionHistoryTableExists();
        $this->testOrdersTableHasRevisionFields();
        $this->testGetRevisionHistoryMethod();
        $this->testGetCurrentRevisionMethod();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testRevisionHistoryTableExists(): void
    {
        try {
            $stmt = $this->db->query("DESCRIBE order_revision_history");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredColumns = ['id', 'order_id', 'revision_reason', 'requested_by', 'requested_at', 'revision_number', 'is_current'];
            $hasAllColumns = true;
            
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $columns)) {
                    $hasAllColumns = false;
                    break;
                }
            }
            
            $this->assert(
                $hasAllColumns,
                "order_revision_history table has all required columns"
            );
        } catch (Exception $e) {
            $this->assert(false, "order_revision_history table exists: " . $e->getMessage());
        }
    }

    private function testOrdersTableHasRevisionFields(): void
    {
        try {
            $stmt = $this->db->query("DESCRIBE orders");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $this->assert(
                in_array('current_revision_id', $columns),
                "orders table has current_revision_id column"
            );
            
            $this->assert(
                in_array('revision_history_count', $columns),
                "orders table has revision_history_count column"
            );
        } catch (Exception $e) {
            $this->assert(false, "orders table has revision fields: " . $e->getMessage());
        }
    }

    private function testGetRevisionHistoryMethod(): void
    {
        try {
            // Test with a non-existent order (should return empty array)
            $history = $this->orderRepository->getRevisionHistory(999999);
            
            $this->assert(
                is_array($history),
                "getRevisionHistory returns an array"
            );
            
            $this->assert(
                empty($history),
                "getRevisionHistory returns empty array for non-existent order"
            );
        } catch (Exception $e) {
            $this->assert(false, "getRevisionHistory method works: " . $e->getMessage());
        }
    }

    private function testGetCurrentRevisionMethod(): void
    {
        try {
            // Test with a non-existent order (should return null)
            $current = $this->orderRepository->getCurrentRevision(999999);
            
            $this->assert(
                $current === null,
                "getCurrentRevision returns null for non-existent order"
            );
        } catch (Exception $e) {
            $this->assert(false, "getCurrentRevision method works: " . $e->getMessage());
        }
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "✓ PASS: {$message}\n";
            $this->testsPassed++;
        } else {
            echo "✗ FAIL: {$message}\n";
            $this->testsFailed++;
        }
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $test = new RevisionHistoryTest();
    $test->run();
}
