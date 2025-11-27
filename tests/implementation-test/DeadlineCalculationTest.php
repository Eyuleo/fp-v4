<?php

/**
 * Deadline Calculation Test
 * 
 * Tests for accurate order deadline calculation using DateTime
 * Validates Requirements 2.1, 2.2, 2.3, 2.5
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/OrderService.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';
require_once __DIR__ . '/../src/Repositories/ServiceRepository.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';

class DeadlineCalculationTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private OrderService $orderService;
    private ServiceRepository $serviceRepository;
    private OrderRepository $orderRepository;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
        $this->orderRepository = new OrderRepository($this->db);
        $this->serviceRepository = new ServiceRepository($this->db);
        $paymentRepository = new PaymentRepository($this->db);
        $paymentService = new PaymentService($paymentRepository, $this->db);
        
        $this->orderService = new OrderService($this->orderRepository, $this->serviceRepository, $paymentService);
    }

    public function run(): void
    {
        echo "Running Deadline Calculation Tests...\n\n";
        
        $this->testDeadlineCalculationAccuracy();
        $this->testFourDayDeliveryDeadline();
        $this->testDeadlineIsInFuture();
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n\n";
        
        exit($this->testsFailed > 0 ? 1 : 0);
    }

    /**
     * Test: Deadline calculation accuracy (Requirement 2.1, 2.2, 2.3)
     * Verifies that deadline is calculated correctly using DateTime
     */
    private function testDeadlineCalculationAccuracy(): void
    {
        echo "Test: Deadline calculation accuracy for various delivery times\n";
        
        $this->db->beginTransaction();
        
        try {
            // Create test user
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'client', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'deadline_client@test.com',
                'password' => password_hash('test', PASSWORD_DEFAULT)
            ]);
            $clientId = (int) $this->db->lastInsertId();
            
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'student', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'deadline_student@test.com',
                'password' => password_hash('test', PASSWORD_DEFAULT)
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
            
            // Test different delivery times
            $deliveryDays = [1, 4, 7, 14, 30];
            
            foreach ($deliveryDays as $days) {
                // Create test service
                $stmt = $this->db->prepare("
                    INSERT INTO services (student_id, category_id, title, description, price, delivery_days, status, created_at, updated_at)
                    VALUES (:student_id, 1, 'Test Service', 'Test', 100.00, :delivery_days, 'active', NOW(), NOW())
                ");
                $stmt->execute([
                    'student_id' => $studentId,
                    'delivery_days' => $days
                ]);
                $serviceId = (int) $this->db->lastInsertId();
                
                // Create order
                $beforeTime = new DateTime('now', new DateTimeZone('UTC'));
                $result = $this->orderService->createOrder($clientId, $serviceId, [
                    'requirements' => 'Test requirements'
                ]);
                $afterTime = new DateTime('now', new DateTimeZone('UTC'));
                
                $this->assert($result['success'], "Order creation succeeds for {$days}-day delivery");
                
                if ($result['success']) {
                    $order = $result['order'];
                    $deadline = new DateTime($order['deadline'], new DateTimeZone('UTC'));
                    
                    // Calculate expected deadline range (allow 1 minute buffer for test execution)
                    $expectedMin = clone $beforeTime;
                    $expectedMin->add(new DateInterval('P' . $days . 'D'));
                    $expectedMin->sub(new DateInterval('PT1M')); // 1 minute buffer
                    
                    $expectedMax = clone $afterTime;
                    $expectedMax->add(new DateInterval('P' . $days . 'D'));
                    $expectedMax->add(new DateInterval('PT1M')); // 1 minute buffer
                    
                    // Verify deadline is within expected range
                    $this->assert(
                        $deadline >= $expectedMin && $deadline <= $expectedMax,
                        "Deadline for {$days}-day delivery is within expected range ({$days} days +/- 1 minute)"
                    );
                    
                    // Verify it's not in hours (the bug we're fixing)
                    $hoursDiff = ($deadline->getTimestamp() - $beforeTime->getTimestamp()) / 3600;
                    $daysDiff = $hoursDiff / 24;
                    
                    $this->assert(
                        abs($daysDiff - $days) < 0.1, // Allow small floating point difference
                        "Deadline is in days, not hours (expected ~{$days} days, got " . round($daysDiff, 2) . " days)"
                    );
                }
            }
            
        } finally {
            $this->db->rollBack();
        }
    }

    /**
     * Test: 4-day delivery deadline (Requirement 2.4)
     * Specific test case from requirements: 4-day delivery should show 4 days, not 2 hours
     */
    private function testFourDayDeliveryDeadline(): void
    {
        echo "\nTest: 4-day delivery shows 4 days, not 2 hours\n";
        
        $this->db->beginTransaction();
        
        try {
            // Create test user
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'client', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'fourday_client@test.com',
                'password' => password_hash('test', PASSWORD_DEFAULT)
            ]);
            $clientId = (int) $this->db->lastInsertId();
            
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'student', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'fourday_student@test.com',
                'password' => password_hash('test', PASSWORD_DEFAULT)
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
            
            // Create 4-day delivery service
            $stmt = $this->db->prepare("
                INSERT INTO services (student_id, category_id, title, description, price, delivery_days, status, created_at, updated_at)
                VALUES (:student_id, 1, 'Test Service', 'Test', 100.00, 4, 'active', NOW(), NOW())
            ");
            $stmt->execute(['student_id' => $studentId]);
            $serviceId = (int) $this->db->lastInsertId();
            
            // Create order
            $beforeTime = time();
            $result = $this->orderService->createOrder($clientId, $serviceId, [
                'requirements' => 'Test requirements'
            ]);
            
            $this->assert($result['success'], "Order creation succeeds");
            
            if ($result['success']) {
                $order = $result['order'];
                $deadlineTimestamp = strtotime($order['deadline']);
                
                // Calculate hours and days difference
                $hoursDiff = ($deadlineTimestamp - $beforeTime) / 3600;
                $daysDiff = $hoursDiff / 24;
                
                // Should be approximately 4 days (96 hours), not 2 hours
                $this->assert(
                    $hoursDiff > 90 && $hoursDiff < 100,
                    "Deadline is approximately 96 hours (4 days), not 2 hours (got " . round($hoursDiff, 2) . " hours)"
                );
                
                $this->assert(
                    $daysDiff > 3.9 && $daysDiff < 4.1,
                    "Deadline is approximately 4 days (got " . round($daysDiff, 2) . " days)"
                );
            }
            
        } finally {
            $this->db->rollBack();
        }
    }

    /**
     * Test: Deadline is always in the future (Requirement 2.5)
     */
    private function testDeadlineIsInFuture(): void
    {
        echo "\nTest: Deadline is always in the future at order creation\n";
        
        $this->db->beginTransaction();
        
        try {
            // Create test user
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'client', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'future_client@test.com',
                'password' => password_hash('test', PASSWORD_DEFAULT)
            ]);
            $clientId = (int) $this->db->lastInsertId();
            
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'student', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'future_student@test.com',
                'password' => password_hash('test', PASSWORD_DEFAULT)
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
            
            // Create service with 1-day delivery
            $stmt = $this->db->prepare("
                INSERT INTO services (student_id, category_id, title, description, price, delivery_days, status, created_at, updated_at)
                VALUES (:student_id, 1, 'Test Service', 'Test', 100.00, 1, 'active', NOW(), NOW())
            ");
            $stmt->execute(['student_id' => $studentId]);
            $serviceId = (int) $this->db->lastInsertId();
            
            // Create order
            $beforeTime = time();
            $result = $this->orderService->createOrder($clientId, $serviceId, [
                'requirements' => 'Test requirements'
            ]);
            
            $this->assert($result['success'], "Order creation succeeds");
            
            if ($result['success']) {
                $order = $result['order'];
                $deadlineTimestamp = strtotime($order['deadline']);
                
                $this->assert(
                    $deadlineTimestamp > $beforeTime,
                    "Deadline is in the future (deadline: {$order['deadline']}, now: " . date('Y-m-d H:i:s', $beforeTime) . ")"
                );
            }
            
        } finally {
            $this->db->rollBack();
        }
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "  ✓ PASS: {$message}\n";
            $this->testsPassed++;
        } else {
            echo "  ✗ FAIL: {$message}\n";
            $this->testsFailed++;
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new DeadlineCalculationTest();
    $test->run();
}
