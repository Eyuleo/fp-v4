<?php

/**
 * Message Service Suspension Test
 *
 * Tests for MessageService suspension checking functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/MessageService.php';
require_once __DIR__ . '/../src/Repositories/MessageRepository.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';

class MessageServiceSuspensionTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private MessageService $messageService;
    private UserRepository $userRepository;
    private OrderRepository $orderRepository;

    public function __construct()
    {
        // Use test database connection
        $this->db = getDatabaseConnection();
        $messageRepository = new MessageRepository($this->db);
        $this->orderRepository = new OrderRepository($this->db);
        $this->userRepository = new UserRepository($this->db);
        
        $this->messageService = new MessageService($messageRepository, $this->orderRepository);
    }

    public function run(): void
    {
        echo "Running Message Service Suspension Tests...\n\n";

        $this->testSuspendedUserCannotSendMessage();
        $this->testTemporarySuspensionErrorMessage();
        $this->testPermanentBanErrorMessage();
        $this->testActiveUserCanSendMessage();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testSuspendedUserCannotSendMessage(): void
    {
        echo "Test: Suspended user cannot send message\n";

        // Create test users and order
        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Suspend the sender
        $this->userRepository->setSuspension($testData['sender_id'], 7);

        // Attempt to send message
        $result = $this->messageService->sendMessage(
            $testData['sender_id'],
            $testData['order_id'],
            'Test message',
            []
        );

        $this->assert(!$result['success'], "Message sending fails for suspended user");
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
        $this->userRepository->setSuspension($testData['sender_id'], 7);

        // Attempt to send message
        $result = $this->messageService->sendMessage(
            $testData['sender_id'],
            $testData['order_id'],
            'Test message',
            []
        );

        $this->assert(!$result['success'], "Message sending fails");
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
        echo "\nTest: Permanent ban error message indicates permanence\n";

        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Apply permanent ban (null days)
        $this->userRepository->setSuspension($testData['sender_id'], null);

        // Attempt to send message
        $result = $this->messageService->sendMessage(
            $testData['sender_id'],
            $testData['order_id'],
            'Test message',
            []
        );

        $this->assert(!$result['success'], "Message sending fails");
        $this->assert(isset($result['errors']['suspension']), "Suspension error exists");
        $this->assert(
            strpos($result['errors']['suspension'], 'permanent') !== false,
            "Error message indicates permanent suspension"
        );

        // Cleanup
        $this->cleanupTestData($testData);
    }

    private function testActiveUserCanSendMessage(): void
    {
        echo "\nTest: Active user can send message\n";

        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Ensure user is active (not suspended)
        $this->userRepository->clearSuspension($testData['sender_id']);

        // Attempt to send message
        $result = $this->messageService->sendMessage(
            $testData['sender_id'],
            $testData['order_id'],
            'Test message from active user',
            []
        );

        $this->assert($result['success'], "Message sending succeeds for active user");
        $this->assert(!isset($result['errors']['suspension']), "No suspension error");
        $this->assert($result['message_id'] > 0, "Message ID is returned");

        // Cleanup
        $this->cleanupTestData($testData);
    }

    private function createTestData(): ?array
    {
        try {
            $this->db->beginTransaction();

            // Create test sender
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'client', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'test_sender_' . time() . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
            $senderId = (int) $this->db->lastInsertId();

            // Create test recipient
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'student', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'test_recipient_' . time() . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
            $recipientId = (int) $this->db->lastInsertId();

            // Create student profile for recipient
            $stmt = $this->db->prepare("
                INSERT INTO student_profiles (user_id, bio, skills, created_at, updated_at)
                VALUES (:user_id, 'Test bio', :skills, NOW(), NOW())
            ");
            $stmt->execute([
                'user_id' => $recipientId,
                'skills' => json_encode(['PHP', 'Testing'])
            ]);

            // Create test service
            $stmt = $this->db->prepare("
                INSERT INTO services (student_id, category_id, title, description, price, delivery_days, status, created_at, updated_at)
                VALUES (:student_id, 1, 'Test Service', 'Test Description', 100.00, 7, 'active', NOW(), NOW())
            ");
            $stmt->execute(['student_id' => $recipientId]);
            $serviceId = (int) $this->db->lastInsertId();

            // Create test order
            $stmt = $this->db->prepare("
                INSERT INTO orders (service_id, client_id, student_id, price, commission_rate, requirements, deadline, status, created_at, updated_at)
                VALUES (:service_id, :client_id, :student_id, 100.00, 15.00, 'Test requirements', DATE_ADD(NOW(), INTERVAL 7 DAY), 'in_progress', NOW(), NOW())
            ");
            $stmt->execute([
                'service_id' => $serviceId,
                'client_id' => $senderId,
                'student_id' => $recipientId
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'service_id' => $serviceId,
                'order_id' => $orderId
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
            $this->db->prepare("DELETE FROM messages WHERE order_id = :order_id")
                ->execute(['order_id' => $testData['order_id']]);
            
            $this->db->prepare("DELETE FROM orders WHERE id = :id")
                ->execute(['id' => $testData['order_id']]);
            
            $this->db->prepare("DELETE FROM services WHERE id = :id")
                ->execute(['id' => $testData['service_id']]);
            
            $this->db->prepare("DELETE FROM student_profiles WHERE user_id = :user_id")
                ->execute(['user_id' => $testData['recipient_id']]);
            
            $this->db->prepare("DELETE FROM users WHERE id = :id")
                ->execute(['id' => $testData['sender_id']]);
            
            $this->db->prepare("DELETE FROM users WHERE id = :id")
                ->execute(['id' => $testData['recipient_id']]);
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
    $test = new MessageServiceSuspensionTest();
    $test->run();
}
