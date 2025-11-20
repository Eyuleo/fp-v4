<?php

/**
 * Order Service Suspension Test
 *
 * Tests for OrderService suspension checking functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/OrderService.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';
require_once __DIR__ . '/../src/Repositories/ServiceRepository.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';

class OrderServiceSuspensionTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private OrderService $orderService;
    private UserRepository $userRepository;
    private ServiceRepository $serviceRepository;

    public function __construct()
    {
        // Use test database connection
        $this->db = getDatabaseConnection();
        $orderRepository = new OrderRepository($this->db);
        $this->serviceRepository = new ServiceRepository($this->db);
        $this->userRepository = new UserRepository($this->db);
        
        $paymentRepository = new PaymentRepository($this->db);
        $paymentService = new PaymentService($paymentRepository, $this->db);
        
        $this->orderService = new OrderService($orderRepository, $this->serviceRepository, $paymentService);
    }

    public function run(): void
    {
        echo "Running Order Service Suspension Tests...\n\n";

        $this->testSuspendedClientCannotCreateOrder();
        $this->testTemporarySuspensionErrorMessage();
        $this->testPermanentBanErrorMessage();
        $this->testActiveClientCanCreateOrder();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testSuspendedClientCannotCreateOrder(): void
    {
        echo "Test: Suspended client cannot create order\n";

        // Create test data
        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Suspend the client
        $this->userRepository->setSuspension($testData['client_id'], 7);

        // Attempt to create order
        $result = $this->orderService->createOrder(
            $testData['client_id'],
            $testData['service_id'],
            [
                'requirements' => 'Test requirements for order'
            ]
        );

        $this->assert(!$result['success'], "Order creation fails for suspended client");
        $this->assert(isset($result['errors']['suspension']), "Suspension error is returned");
        $this->assert(
            strpos($result['errors']['suspension'], 'suspended') !== false,
            "Error message mentions suspension"
        );

        // Cleanup
        $this->cleanupTestData($testData);
    }

    private function testTemporarySuspensionErrorMessage(): void
    {
        echo "\nTest: Temporary suspension error message includes end date\n";

        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Apply temporary suspension
        $this->userRepository->setSuspension($testData['client_id'], 7);

        // Attempt to create order
        $result = $this->orderService->createOrder(
            $testData['client_id'],
            $testData['service_id'],
            [
                'requirements' => 'Test requirements for order'
            ]
        );

        $this->assert(!$result['success'], "Order creation fails");
        $this->assert(isset($result['errors']['suspension']), "Suspension error exists");
        $this->assert(
            strpos($result['errors']['suspension'], 'will end on') !== false,
            "Error message includes suspension end date"
        );

        // Cleanup
        $this->cleanupTestData($testData);
    }

    private function testPermanentBanErrorMessage(): void
    {
        echo "\nTest: Permanent ban error message does not include end date\n";

        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Apply permanent ban (null days)
        $this->userRepository->setSuspension($testData['client_id'], null);

        // Attempt to create order
        $result = $this->orderService->createOrder(
            $testData['client_id'],
            $testData['service_id'],
            [
                'requirements' => 'Test requirements for order'
            ]
        );

        $this->assert(!$result['success'], "Order creation fails");
        $this->assert(isset($result['errors']['suspension']), "Suspension error exists");
        $this->assert(
            strpos($result['errors']['suspension'], 'will end on') === false,
            "Error message does not include end date for permanent ban"
        );

        // Cleanup
        $this->cleanupTestData($testData);
    }

    private function testActiveClientCanCreateOrder(): void
    {
        echo "\nTest: Active client can create order\n";

        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Ensure client is active (not suspended)
        $this->userRepository->clearSuspension($testData['client_id']);

        // Attempt to create order
        $result = $this->orderService->createOrder(
            $testData['client_id'],
            $testData['service_id'],
            [
                'requirements' => 'Test requirements for order from active client'
            ]
        );

        $this->assert($result['success'], "Order creation succeeds for active client");
        $this->assert(!isset($result['errors']['suspension']), "No suspension error");
        $this->assert($result['order_id'] > 0, "Order ID is returned");

        // Cleanup order
        if ($result['order_id']) {
            $this->db->prepare("DELETE FROM orders WHERE id = :id")
                ->execute(['id' => $result['order_id']]);
        }
        
        $this->cleanupTestData($testData);
    }

    private function createTestData(): ?array
    {
        try {
            $this->db->beginTransaction();

            // Create test client
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'client', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'test_client_' . time() . rand(1000, 9999) . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
            $clientId = (int) $this->db->lastInsertId();

            // Create test student
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'student', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'test_student_' . time() . rand(1000, 9999) . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
            $studentId = (int) $this->db->lastInsertId();

            // Create student profile
            $stmt = $this->db->prepare("
                INSERT INTO student_profiles (user_id, bio, skills, created_at, updated_at)
                VALUES (:user_id, 'Test bio', :skills, NOW(), NOW())
            ");
            $stmt->execute([
                'user_id' => $studentId,
                'skills' => json_encode(['PHP', 'Testing'])
            ]);

            // Create test service
            $stmt = $this->db->prepare("
                INSERT INTO services (student_id, category_id, title, description, price, delivery_days, status, created_at, updated_at)
                VALUES (:student_id, 1, 'Test Service', 'Test Description', 100.00, 7, 'active', NOW(), NOW())
            ");
            $stmt->execute(['student_id' => $studentId]);
            $serviceId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'client_id' => $clientId,
                'student_id' => $studentId,
                'service_id' => $serviceId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            echo "  ERROR: Failed to create test data: " . $e->getMessage() . "\n";
            return null;
        }
    }

    private function cleanupTestData(?array $testData): void
    {
        if (!$testData) {
            return;
        }

        try {
            // Delete in reverse order of creation to respect foreign keys
            $this->db->prepare("DELETE FROM services WHERE id = :id")
                ->execute(['id' => $testData['service_id']]);
            
            $this->db->prepare("DELETE FROM student_profiles WHERE user_id = :user_id")
                ->execute(['user_id' => $testData['student_id']]);
            
            $this->db->prepare("DELETE FROM users WHERE id = :id")
                ->execute(['id' => $testData['client_id']]);
            
            $this->db->prepare("DELETE FROM users WHERE id = :id")
                ->execute(['id' => $testData['student_id']]);
        } catch (Exception $e) {
            error_log('Failed to cleanup test data: ' . $e->getMessage());
        }
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "  ✓ PASS: $message\n";
            $this->testsPassed++;
        } else {
            echo "  ✗ FAIL: $message\n";
            $this->testsFailed++;
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new OrderServiceSuspensionTest();
    $test->run();
}
