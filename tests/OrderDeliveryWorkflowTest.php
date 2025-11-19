<?php

/**
 * Order Delivery Workflow Test
 *
 * Tests the fixed order delivery workflow
 */

require_once __DIR__ . '/../src/Policies/Policy.php';
require_once __DIR__ . '/../src/Policies/OrderPolicy.php';

class OrderDeliveryWorkflowTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;

    public function run(): void
    {
        echo "Running Order Delivery Workflow Tests...\n\n";

        $this->testDeliveryAccessValidation();
        $this->testDeliveryPermissionChecks();
        $this->testDeadlineValidation();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testDeliveryAccessValidation(): void
    {
        $policy = new OrderPolicy();

        // Test: Student can deliver their own order in in_progress status
        $student = ['id' => 1, 'role' => 'student'];
        $order = [
            'id' => 1,
            'student_id' => 1,
            'status' => 'in_progress',
            'deadline' => date('Y-m-d H:i:s', strtotime('+2 days'))
        ];
        $this->assert(
            $policy->canDeliver($student, $order),
            "Student can deliver order in in_progress status"
        );

        // Test: Student can deliver order in revision_requested status
        $order['status'] = 'revision_requested';
        $this->assert(
            $policy->canDeliver($student, $order),
            "Student can deliver order in revision_requested status"
        );

        // Test: Student cannot deliver order in delivered status
        $order['status'] = 'delivered';
        $this->assert(
            !$policy->canDeliver($student, $order),
            "Student cannot deliver order already in delivered status"
        );

        // Test: Client cannot deliver order
        $client = ['id' => 2, 'role' => 'client'];
        $order['status'] = 'in_progress';
        $this->assert(
            !$policy->canDeliver($client, $order),
            "Client cannot deliver order"
        );
    }

    private function testDeliveryPermissionChecks(): void
    {
        $policy = new OrderPolicy();

        // Test: Student cannot deliver another student's order
        $student = ['id' => 1, 'role' => 'student'];
        $order = [
            'id' => 1,
            'student_id' => 2, // Different student
            'status' => 'in_progress',
            'deadline' => date('Y-m-d H:i:s', strtotime('+2 days'))
        ];
        $this->assert(
            !$policy->canDeliver($student, $order),
            "Student cannot deliver another student's order"
        );

        // Test: Admin cannot deliver order (not a student)
        $admin = ['id' => 3, 'role' => 'admin'];
        $order['student_id'] = 3;
        $this->assert(
            !$policy->canDeliver($admin, $order),
            "Admin cannot deliver order"
        );
    }

    private function testDeadlineValidation(): void
    {
        $policy = new OrderPolicy();

        // Test: Student cannot deliver past deadline
        $student = ['id' => 1, 'role' => 'student'];
        $order = [
            'id' => 1,
            'student_id' => 1,
            'status' => 'in_progress',
            'deadline' => date('Y-m-d H:i:s', strtotime('-1 day')) // Past deadline
        ];
        $this->assert(
            !$policy->canDeliver($student, $order),
            "Student cannot deliver order past deadline"
        );

        // Test: Student can deliver before deadline
        $order['deadline'] = date('Y-m-d H:i:s', strtotime('+1 day'));
        $this->assert(
            $policy->canDeliver($student, $order),
            "Student can deliver order before deadline"
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
    $test = new OrderDeliveryWorkflowTest();
    $test->run();
}
