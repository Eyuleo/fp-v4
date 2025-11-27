<?php

/**
 * Authorization System Test
 *
 * Simple test to verify policy classes work correctly
 */

require_once __DIR__ . '/../src/Policies/Policy.php';
require_once __DIR__ . '/../src/Policies/OrderPolicy.php';
require_once __DIR__ . '/../src/Policies/ServicePolicy.php';
require_once __DIR__ . '/../src/Policies/MessagePolicy.php';

class AuthorizationTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;

    public function run(): void
    {
        echo "Running Authorization Tests...\n\n";

        $this->testOrderPolicyView();
        $this->testOrderPolicyAccept();
        $this->testOrderPolicyDeliver();
        $this->testOrderPolicyComplete();
        $this->testOrderPolicyCancel();
        $this->testServicePolicyEdit();
        $this->testServicePolicyCreate();
        $this->testMessagePolicySend();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testOrderPolicyView(): void
    {
        $policy = new OrderPolicy();

        // Test: Client can view their own order
        $client = ['id' => 1, 'role' => 'client'];
        $order  = ['id' => 1, 'client_id' => 1, 'student_id' => 2, 'status' => 'pending'];
        $this->assert($policy->canView($client, $order), "Client can view their own order");

        // Test: Student can view their order
        $student = ['id' => 2, 'role' => 'student'];
        $this->assert($policy->canView($student, $order), "Student can view their order");

        // Test: Admin can view any order
        $admin = ['id' => 3, 'role' => 'admin'];
        $this->assert($policy->canView($admin, $order), "Admin can view any order");

        // Test: Other client cannot view order
        $otherClient = ['id' => 4, 'role' => 'client'];
        $this->assert(! $policy->canView($otherClient, $order), "Other client cannot view order");
    }

    private function testOrderPolicyAccept(): void
    {
        $policy = new OrderPolicy();

        // Test: Order acceptance has been removed from workflow - always returns false
        $student = ['id' => 2, 'role' => 'student'];
        $order   = ['id' => 1, 'client_id' => 1, 'student_id' => 2, 'status' => 'pending'];
        $this->assert(! $policy->canAccept($student, $order), "Student cannot accept order (workflow simplified)");

        // Test: Client cannot accept order
        $client = ['id' => 1, 'role' => 'client'];
        $this->assert(! $policy->canAccept($client, $order), "Client cannot accept order");

        // Test: Admin cannot accept order
        $admin = ['id' => 3, 'role' => 'admin'];
        $this->assert(! $policy->canAccept($admin, $order), "Admin cannot accept order");
    }

    private function testOrderPolicyDeliver(): void
    {
        $policy = new OrderPolicy();

        // Test: Student can deliver in_progress order (before deadline)
        $student = ['id' => 2, 'role' => 'student'];
        $order   = ['id' => 1, 'client_id' => 1, 'student_id' => 2, 'status' => 'in_progress', 'deadline' => date('Y-m-d H:i:s', strtotime('+1 day'))];
        $this->assert($policy->canDeliver($student, $order), "Student can deliver in_progress order before deadline");

        // Test: Student can deliver revision_requested order (before deadline)
        $order['status'] = 'revision_requested';
        $this->assert($policy->canDeliver($student, $order), "Student can deliver revision_requested order before deadline");

        // Test: Student cannot deliver order past deadline
        $order['deadline'] = date('Y-m-d H:i:s', strtotime('-1 day'));
        $this->assert(! $policy->canDeliver($student, $order), "Student cannot deliver order past deadline");

        // Test: Student cannot deliver order without deadline set (before deadline)
        unset($order['deadline']);
        $order['status'] = 'in_progress';
        $this->assert($policy->canDeliver($student, $order), "Student can deliver order without deadline set");

        // Test: Client cannot deliver order
        $client = ['id' => 1, 'role' => 'client'];
        $this->assert(! $policy->canDeliver($client, $order), "Client cannot deliver order");

        // Test: Student cannot deliver completed order
        $order['status'] = 'completed';
        $this->assert(! $policy->canDeliver($student, $order), "Student cannot deliver completed order");
    }

    private function testOrderPolicyComplete(): void
    {
        $policy = new OrderPolicy();

        // Test: Client can complete delivered order
        $client = ['id' => 1, 'role' => 'client'];
        $order  = ['id' => 1, 'client_id' => 1, 'student_id' => 2, 'status' => 'delivered'];
        $this->assert($policy->canComplete($client, $order), "Client can complete delivered order");

        // Test: Student cannot complete order
        $student = ['id' => 2, 'role' => 'student'];
        $this->assert(! $policy->canComplete($student, $order), "Student cannot complete order");

        // Test: Client cannot complete pending order
        $order['status'] = 'pending';
        $this->assert(! $policy->canComplete($client, $order), "Client cannot complete pending order");
    }

    private function testOrderPolicyCancel(): void
    {
        $policy = new OrderPolicy();

        // Test: Only admins can cancel orders (workflow simplified)
        $client = ['id' => 1, 'role' => 'client'];
        $order  = ['id' => 1, 'client_id' => 1, 'student_id' => 2, 'status' => 'pending'];
        $this->assert(! $policy->canCancel($client, $order), "Client cannot cancel order");

        // Test: Student cannot cancel order
        $student = ['id' => 2, 'role' => 'student'];
        $this->assert(! $policy->canCancel($student, $order), "Student cannot cancel order");

        // Test: Admin can cancel any order
        $admin = ['id' => 3, 'role' => 'admin'];
        $this->assert($policy->canCancel($admin, $order), "Admin can cancel pending order");

        // Test: Admin can cancel in_progress order
        $order['status'] = 'in_progress';
        $this->assert($policy->canCancel($admin, $order), "Admin can cancel in_progress order");

        // Test: Admin can cancel delivered order
        $order['status'] = 'delivered';
        $this->assert($policy->canCancel($admin, $order), "Admin can cancel delivered order");
    }

    private function testServicePolicyEdit(): void
    {
        $policy = new ServicePolicy();

        // Test: Student can edit their own service
        $student = ['id' => 1, 'role' => 'student'];
        $service = ['id' => 1, 'student_id' => 1, 'status' => 'active'];
        $this->assert($policy->canEdit($student, $service), "Student can edit their own service");

        // Test: Other student cannot edit service
        $otherStudent = ['id' => 2, 'role' => 'student'];
        $this->assert(! $policy->canEdit($otherStudent, $service), "Other student cannot edit service");

        // Test: Client cannot edit service
        $client = ['id' => 3, 'role' => 'client'];
        $this->assert(! $policy->canEdit($client, $service), "Client cannot edit service");

        // Test: Admin can edit any service
        $admin = ['id' => 4, 'role' => 'admin'];
        $this->assert($policy->canEdit($admin, $service), "Admin can edit any service");
    }

    private function testServicePolicyCreate(): void
    {
        $policy = new ServicePolicy();

        // Test: Student can create services
        $student = ['id' => 1, 'role' => 'student'];
        $this->assert($policy->canCreate($student), "Student can create services");

        // Test: Client cannot create services
        $client = ['id' => 2, 'role' => 'client'];
        $this->assert(! $policy->canCreate($client), "Client cannot create services");

        // Test: Admin cannot create services (not a student)
        $admin = ['id' => 3, 'role' => 'admin'];
        $this->assert(! $policy->canCreate($admin), "Admin cannot create services");
    }

    private function testMessagePolicySend(): void
    {
        $policy = new MessagePolicy();

        // Test: Client can send messages in their order
        $client = ['id' => 1, 'role' => 'client'];
        $order  = ['id' => 1, 'client_id' => 1, 'student_id' => 2];
        $this->assert($policy->canSend($client, $order), "Client can send messages in their order");

        // Test: Student can send messages in their order
        $student = ['id' => 2, 'role' => 'student'];
        $this->assert($policy->canSend($student, $order), "Student can send messages in their order");

        // Test: Admin can send messages in any order
        $admin = ['id' => 3, 'role' => 'admin'];
        $this->assert($policy->canSend($admin, $order), "Admin can send messages in any order");

        // Test: Other client cannot send messages
        $otherClient = ['id' => 4, 'role' => 'client'];
        $this->assert(! $policy->canSend($otherClient, $order), "Other client cannot send messages");
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "✓ PASS: $message\n";
            $this->testsPassed++;
        } else {
            echo "✗ FAIL: $message\n";
            $this->testsFailed++;
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new AuthorizationTest();
    $test->run();
}
