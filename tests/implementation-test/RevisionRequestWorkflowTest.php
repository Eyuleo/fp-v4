<?php

/**
 * Revision Request Workflow Test
 *
 * Tests the revision request workflow implementation
 */

require_once __DIR__ . '/../src/Policies/Policy.php';
require_once __DIR__ . '/../src/Policies/OrderPolicy.php';

class RevisionRequestWorkflowTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;

    public function run(): void
    {
        echo "Running Revision Request Workflow Tests...\n\n";

        $this->testRevisionAccessValidation();
        $this->testRevisionPermissionChecks();
        $this->testRevisionLimitValidation();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testRevisionAccessValidation(): void
    {
        $policy = new OrderPolicy();

        // Test: Client can request revision on delivered order
        $client = ['id' => 1, 'role' => 'client'];
        $order = [
            'id' => 1,
            'client_id' => 1,
            'status' => 'delivered',
            'revision_count' => 0,
            'max_revisions' => 3
        ];
        $this->assert(
            $policy->canRequestRevision($client, $order),
            "Client can request revision on delivered order"
        );

        // Test: Client cannot request revision on in_progress order
        $order['status'] = 'in_progress';
        $this->assert(
            !$policy->canRequestRevision($client, $order),
            "Client cannot request revision on in_progress order"
        );

        // Test: Client cannot request revision on completed order
        $order['status'] = 'completed';
        $this->assert(
            !$policy->canRequestRevision($client, $order),
            "Client cannot request revision on completed order"
        );

        // Test: Student cannot request revision
        $student = ['id' => 2, 'role' => 'student'];
        $order['status'] = 'delivered';
        $this->assert(
            !$policy->canRequestRevision($student, $order),
            "Student cannot request revision"
        );
    }

    private function testRevisionPermissionChecks(): void
    {
        $policy = new OrderPolicy();

        // Test: Client cannot request revision on another client's order
        $client = ['id' => 1, 'role' => 'client'];
        $order = [
            'id' => 1,
            'client_id' => 2, // Different client
            'status' => 'delivered',
            'revision_count' => 0,
            'max_revisions' => 3
        ];
        $this->assert(
            !$policy->canRequestRevision($client, $order),
            "Client cannot request revision on another client's order"
        );

        // Test: Admin cannot request revision (not a client)
        $admin = ['id' => 3, 'role' => 'admin'];
        $order['client_id'] = 3;
        $this->assert(
            !$policy->canRequestRevision($admin, $order),
            "Admin cannot request revision"
        );
    }

    private function testRevisionLimitValidation(): void
    {
        $policy = new OrderPolicy();

        // Test: Client cannot request revision when limit reached
        $client = ['id' => 1, 'role' => 'client'];
        $order = [
            'id' => 1,
            'client_id' => 1,
            'status' => 'delivered',
            'revision_count' => 3,
            'max_revisions' => 3
        ];
        $this->assert(
            !$policy->canRequestRevision($client, $order),
            "Client cannot request revision when limit reached"
        );

        // Test: Client can request revision when under limit
        $order['revision_count'] = 2;
        $this->assert(
            $policy->canRequestRevision($client, $order),
            "Client can request revision when under limit"
        );

        // Test: Client can request first revision
        $order['revision_count'] = 0;
        $this->assert(
            $policy->canRequestRevision($client, $order),
            "Client can request first revision"
        );
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
    $test = new RevisionRequestWorkflowTest();
    $test->run();
}
