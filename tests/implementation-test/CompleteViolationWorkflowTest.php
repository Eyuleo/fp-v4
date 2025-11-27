<?php

/**
 * Complete Violation Workflow Test
 *
 * Integration test for the complete violation workflow including:
 * - Message flagging
 * - Violation confirmation
 * - Penalty application
 * - Notification sending
 * - Audit log creation
 * - Suspension enforcement across platform
 *
 * Requirements: 2.1, 2.4, 3.1, 3.2, 3.3, 3.4, 5.2, 12.1, 12.2, 12.3
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/ViolationService.php';
require_once __DIR__ . '/../src/Services/MessageService.php';
require_once __DIR__ . '/../src/Services/OrderService.php';
require_once __DIR__ . '/../src/Services/ServiceService.php';
require_once __DIR__ . '/../src/Repositories/ViolationRepository.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Repositories/MessageRepository.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';
require_once __DIR__ . '/../src/Repositories/ServiceRepository.php';
require_once __DIR__ . '/../src/Repositories/PaymentRepository.php';
require_once __DIR__ . '/../src/Services/NotificationService.php';
require_once __DIR__ . '/../src/Services/MailService.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';
require_once __DIR__ . '/../src/Repositories/NotificationRepository.php';

class CompleteViolationWorkflowTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private ViolationService $violationService;
    private MessageService $messageService;
    private OrderService $orderService;
    private ServiceService $serviceService;
    private UserRepository $userRepository;
    private ViolationRepository $violationRepository;
    private MessageRepository $messageRepository;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
        
        // Initialize repositories
        $this->violationRepository = new ViolationRepository($this->db);
        $this->userRepository = new UserRepository($this->db);
        $this->messageRepository = new MessageRepository($this->db);
        $orderRepository = new OrderRepository($this->db);
        $serviceRepository = new ServiceRepository($this->db);
        $paymentRepository = new PaymentRepository($this->db);
        $notificationRepository = new NotificationRepository($this->db);
        
        // Initialize services
        $mailService = new MailService();
        $notificationService = new NotificationService($mailService, $notificationRepository);
        $paymentService = new PaymentService($paymentRepository, $this->db);
        
        $this->violationService = new ViolationService(
            $this->violationRepository,
            $this->userRepository,
            $this->messageRepository,
            $notificationService,
            $this->db
        );
        
        $this->messageService = new MessageService($this->messageRepository, $orderRepository);
        $this->orderService = new OrderService($orderRepository, $serviceRepository, $paymentService);
        $this->serviceService = new ServiceService($serviceRepository);
    }

    public function run(): void
    {
        echo "Running Complete Violation Workflow Tests...\n\n";

        $this->testWarningPenaltyWorkflow();
        $this->testTemporarySuspensionWorkflow();
        $this->testPermanentBanWorkflow();
        $this->testSuspensionEnforcementAcrossPlatform();
        $this->testViolationAuditLogging();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    /**
     * Test complete workflow for warning penalty
     * Requirements: 2.1, 2.4, 3.1, 5.2
     */
    private function testWarningPenaltyWorkflow(): void
    {
        echo "Test: Complete warning penalty workflow\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Step 1: Create a flagged message
        $messageId = $this->createFlaggedMessage($testData);
        $this->assert($messageId > 0, "Flagged message created");

        // Step 2: Confirm violation with warning penalty
        $result = $this->violationService->confirmViolation(
            $messageId,
            $testData['admin_id'],
            [
                'violation_type' => 'off_platform_contact',
                'severity' => 'warning',
                'penalty_type' => 'warning',
                'admin_notes' => 'First offense - warning issued'
            ]
        );

        $this->assert($result['success'], "Violation confirmation succeeds");
        $this->assert(isset($result['violation_id']), "Violation ID returned");

        // Step 3: Verify violation record created (Requirement 2.1)
        $violations = $this->violationRepository->findByUserId($testData['user_id']);
        $this->assert(count($violations) === 1, "Violation record created");
        $this->assert($violations[0]['penalty_type'] === 'warning', "Penalty type is warning");
        $this->assert($violations[0]['message_id'] == $messageId, "Violation linked to message");

        // Step 4: Verify user status unchanged (Requirement 3.1)
        $user = $this->userRepository->findById($testData['user_id']);
        $this->assert($user['status'] === 'active', "User status remains active for warning");
        $this->assert($user['suspension_end_date'] === null, "No suspension date set");

        // Step 5: Verify audit log created (Requirement 2.4)
        $auditLog = $this->getLatestAuditLog($testData['admin_id']);
        $this->assert($auditLog !== null, "Audit log entry created");
        if ($auditLog) {
            $this->assert(
                strpos($auditLog['action'], 'violation') !== false,
                "Audit log mentions violation"
            );
        }

        // Step 6: Verify notification sent (Requirement 5.2)
        $notifications = $this->getNotificationsForUser($testData['user_id']);
        $this->assert(count($notifications) > 0, "Notification sent to user");

        $this->cleanupTestData($testData, $messageId);
    }

    /**
     * Test complete workflow for temporary suspension
     * Requirements: 2.1, 2.4, 3.2, 3.4, 5.2, 12.1, 12.2, 12.3
     */
    private function testTemporarySuspensionWorkflow(): void
    {
        echo "\nTest: Complete temporary suspension workflow\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Step 1: Create a flagged message
        $messageId = $this->createFlaggedMessage($testData);

        // Step 2: Confirm violation with temporary suspension
        $result = $this->violationService->confirmViolation(
            $messageId,
            $testData['admin_id'],
            [
                'violation_type' => 'payment_circumvention',
                'severity' => 'major',
                'penalty_type' => 'temp_suspension',
                'suspension_days' => 7,
                'admin_notes' => 'Repeat offense - 7 day suspension'
            ]
        );

        $this->assert($result['success'], "Violation confirmation succeeds");

        // Step 3: Verify violation record created (Requirement 2.1)
        $violations = $this->violationRepository->findByUserId($testData['user_id']);
        $this->assert(count($violations) === 1, "Violation record created");
        $this->assert($violations[0]['penalty_type'] === 'temp_suspension', "Penalty type is temp_suspension");
        $this->assert($violations[0]['suspension_days'] == 7, "Suspension days recorded");

        // Step 4: Verify user suspended with end date (Requirement 3.2)
        $user = $this->userRepository->findById($testData['user_id']);
        $this->assert($user['status'] === 'suspended', "User status is suspended");
        $this->assert($user['suspension_end_date'] !== null, "Suspension end date set");

        // Step 5: Verify suspension enforcement - messages (Requirement 3.4, 12.1)
        $messageResult = $this->messageService->sendMessage(
            $testData['user_id'],
            $testData['order_id'],
            'Test message from suspended user',
            []
        );
        $this->assert(!$messageResult['success'], "Suspended user cannot send messages");
        $this->assert(
            isset($messageResult['errors']['suspension']),
            "Suspension error returned for messages"
        );

        // Step 6: Verify suspension enforcement - orders (Requirement 12.2)
        $orderResult = $this->orderService->createOrder(
            $testData['user_id'],
            $testData['service_id'],
            ['requirements' => 'Test order from suspended user']
        );
        $this->assert(!$orderResult['success'], "Suspended user cannot create orders");
        $this->assert(
            isset($orderResult['errors']['suspension']),
            "Suspension error returned for orders"
        );

        // Step 7: Verify suspension enforcement - services (Requirement 12.3)
        // Change user role to student for service creation test
        $this->db->prepare("UPDATE users SET role = 'student' WHERE id = :id")
            ->execute(['id' => $testData['user_id']]);
        
        $serviceResult = $this->serviceService->createService(
            $testData['user_id'],
            [
                'category_id' => 1,
                'title' => 'Test Service',
                'description' => 'Test',
                'price' => 100,
                'delivery_days' => 7,
                'tags' => 'test'
            ]
        );
        $this->assert(!$serviceResult['success'], "Suspended user cannot create services");
        $this->assert(
            isset($serviceResult['errors']['suspension']),
            "Suspension error returned for services"
        );

        // Step 8: Verify audit log (Requirement 2.4)
        $auditLog = $this->getLatestAuditLog($testData['admin_id']);
        $this->assert($auditLog !== null, "Audit log entry created");

        $this->cleanupTestData($testData, $messageId);
    }

    /**
     * Test complete workflow for permanent ban
     * Requirements: 2.1, 2.4, 3.3, 3.4, 5.2
     */
    private function testPermanentBanWorkflow(): void
    {
        echo "\nTest: Complete permanent ban workflow\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Step 1: Create a flagged message
        $messageId = $this->createFlaggedMessage($testData);

        // Step 2: Confirm violation with permanent ban
        $result = $this->violationService->confirmViolation(
            $messageId,
            $testData['admin_id'],
            [
                'violation_type' => 'payment_circumvention',
                'severity' => 'critical',
                'penalty_type' => 'permanent_ban',
                'admin_notes' => 'Severe violation - permanent ban'
            ]
        );

        $this->assert($result['success'], "Violation confirmation succeeds");

        // Step 3: Verify violation record created (Requirement 2.1)
        $violations = $this->violationRepository->findByUserId($testData['user_id']);
        $this->assert(count($violations) === 1, "Violation record created");
        $this->assert($violations[0]['penalty_type'] === 'permanent_ban', "Penalty type is permanent_ban");

        // Step 4: Verify user permanently banned (Requirement 3.3)
        $user = $this->userRepository->findById($testData['user_id']);
        $this->assert($user['status'] === 'suspended', "User status is suspended");
        $this->assert($user['suspension_end_date'] === null, "No suspension end date (permanent)");

        // Step 5: Verify suspension enforcement (Requirement 3.4)
        $messageResult = $this->messageService->sendMessage(
            $testData['user_id'],
            $testData['order_id'],
            'Test message from banned user',
            []
        );
        $this->assert(!$messageResult['success'], "Banned user cannot send messages");
        $this->assert(
            strpos($messageResult['errors']['suspension'], 'permanent') !== false,
            "Error message indicates permanent ban"
        );

        // Step 6: Verify audit log (Requirement 2.4)
        $auditLog = $this->getLatestAuditLog($testData['admin_id']);
        $this->assert($auditLog !== null, "Audit log entry created");

        $this->cleanupTestData($testData, $messageId);
    }

    /**
     * Test suspension enforcement across all platform features
     * Requirements: 3.4, 12.1, 12.2, 12.3
     */
    private function testSuspensionEnforcementAcrossPlatform(): void
    {
        echo "\nTest: Suspension enforcement across platform\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Apply suspension directly
        $this->userRepository->setSuspension($testData['user_id'], 7);

        // Test 1: Message blocking (Requirement 12.1)
        $messageResult = $this->messageService->sendMessage(
            $testData['user_id'],
            $testData['order_id'],
            'Test message',
            []
        );
        $this->assert(!$messageResult['success'], "Messages blocked for suspended user");

        // Test 2: Order creation blocking (Requirement 12.2)
        $orderResult = $this->orderService->createOrder(
            $testData['user_id'],
            $testData['service_id'],
            ['requirements' => 'Test order']
        );
        $this->assert(!$orderResult['success'], "Order creation blocked for suspended user");

        // Test 3: Service creation blocking (Requirement 12.3)
        $this->db->prepare("UPDATE users SET role = 'student' WHERE id = :id")
            ->execute(['id' => $testData['user_id']]);
        
        $serviceResult = $this->serviceService->createService(
            $testData['user_id'],
            [
                'category_id' => 1,
                'title' => 'Test Service',
                'description' => 'Test',
                'price' => 100,
                'delivery_days' => 7,
                'tags' => 'test'
            ]
        );
        $this->assert(!$serviceResult['success'], "Service creation blocked for suspended user");

        // Test 4: Verify all errors mention suspension
        $this->assert(
            isset($messageResult['errors']['suspension']),
            "Message error mentions suspension"
        );
        $this->assert(
            isset($orderResult['errors']['suspension']),
            "Order error mentions suspension"
        );
        $this->assert(
            isset($serviceResult['errors']['suspension']),
            "Service error mentions suspension"
        );

        $this->cleanupTestData($testData);
    }

    /**
     * Test audit logging for violation actions
     * Requirement: 2.4
     */
    private function testViolationAuditLogging(): void
    {
        echo "\nTest: Violation audit logging\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Create flagged message and confirm violation
        $messageId = $this->createFlaggedMessage($testData);
        
        $result = $this->violationService->confirmViolation(
            $messageId,
            $testData['admin_id'],
            [
                'violation_type' => 'off_platform_contact',
                'severity' => 'minor',
                'penalty_type' => 'warning',
                'admin_notes' => 'Test violation for audit logging'
            ]
        );

        $this->assert($result['success'], "Violation confirmed");

        // Verify audit log contains required information
        $auditLog = $this->getLatestAuditLog($testData['admin_id']);
        
        $this->assert($auditLog !== null, "Audit log entry exists");
        $this->assert($auditLog['user_id'] == $testData['admin_id'], "Audit log contains user_id (admin)");
        $this->assert(
            strpos($auditLog['action'], 'violation') !== false || 
            strpos($auditLog['action'], 'confirm') !== false,
            "Audit log action describes violation confirmation"
        );

        // Test dismissal also creates audit log
        $messageId2 = $this->createFlaggedMessage($testData);
        $dismissResult = $this->violationService->dismissFlag($messageId2, $testData['admin_id']);
        
        $this->assert($dismissResult['success'], "Flag dismissed");
        
        $auditLog2 = $this->getLatestAuditLog($testData['admin_id']);
        $this->assert($auditLog2 !== null, "Audit log created for dismissal");

        $this->cleanupTestData($testData, $messageId, $messageId2);
    }

    // Helper methods

    private function createTestData(): ?array
    {
        try {
            $this->db->beginTransaction();

            // Create admin user
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'admin', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'test_admin_' . time() . rand(1000, 9999) . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
            $adminId = (int) $this->db->lastInsertId();

            // Create test user (violator)
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'client', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'test_user_' . time() . rand(1000, 9999) . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
            $userId = (int) $this->db->lastInsertId();

            // Create student for orders
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
                'skills' => json_encode(['PHP'])
            ]);

            // Create service
            $stmt = $this->db->prepare("
                INSERT INTO services (student_id, category_id, title, description, price, delivery_days, status, created_at, updated_at)
                VALUES (:student_id, 1, 'Test Service', 'Test', 100.00, 7, 'active', NOW(), NOW())
            ");
            $stmt->execute(['student_id' => $studentId]);
            $serviceId = (int) $this->db->lastInsertId();

            // Create order
            $stmt = $this->db->prepare("
                INSERT INTO orders (service_id, client_id, student_id, price, commission_rate, requirements, deadline, status, created_at, updated_at)
                VALUES (:service_id, :client_id, :student_id, 100.00, 15.00, 'Test', DATE_ADD(NOW(), INTERVAL 7 DAY), 'in_progress', NOW(), NOW())
            ");
            $stmt->execute([
                'service_id' => $serviceId,
                'client_id' => $userId,
                'student_id' => $studentId
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'admin_id' => $adminId,
                'user_id' => $userId,
                'student_id' => $studentId,
                'service_id' => $serviceId,
                'order_id' => $orderId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            echo "  ERROR: Failed to create test data: " . $e->getMessage() . "\n";
            return null;
        }
    }

    private function createFlaggedMessage(array $testData): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO messages (order_id, sender_id, content, is_flagged, created_at)
            VALUES (:order_id, :sender_id, :content, TRUE, NOW())
        ");
        $stmt->execute([
            'order_id' => $testData['order_id'],
            'sender_id' => $testData['user_id'],
            'content' => 'Contact me at email@example.com for payment'
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    private function getLatestAuditLog(int $adminId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM audit_logs 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $adminId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    private function getNotificationsForUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function cleanupTestData(?array $testData, int ...$messageIds): void
    {
        if (!$testData) {
            return;
        }

        try {
            // Delete messages
            foreach ($messageIds as $messageId) {
                if ($messageId) {
                    $this->db->prepare("DELETE FROM messages WHERE id = :id")
                        ->execute(['id' => $messageId]);
                }
            }
            
            // Delete violations
            $this->db->prepare("DELETE FROM user_violations WHERE user_id = :user_id")
                ->execute(['user_id' => $testData['user_id']]);
            
            // Delete notifications
            $this->db->prepare("DELETE FROM notifications WHERE user_id = :user_id")
                ->execute(['user_id' => $testData['user_id']]);
            
            // Delete audit logs
            $this->db->prepare("DELETE FROM audit_logs WHERE user_id = :user_id")
                ->execute(['user_id' => $testData['admin_id']]);
            
            // Delete order-related data
            if (isset($testData['order_id'])) {
                $this->db->prepare("DELETE FROM messages WHERE order_id = :order_id")
                    ->execute(['order_id' => $testData['order_id']]);
                $this->db->prepare("DELETE FROM orders WHERE id = :id")
                    ->execute(['id' => $testData['order_id']]);
            }
            
            if (isset($testData['service_id'])) {
                $this->db->prepare("DELETE FROM services WHERE id = :id")
                    ->execute(['id' => $testData['service_id']]);
            }
            
            if (isset($testData['student_id'])) {
                $this->db->prepare("DELETE FROM student_profiles WHERE user_id = :user_id")
                    ->execute(['user_id' => $testData['student_id']]);
                $this->db->prepare("DELETE FROM users WHERE id = :id")
                    ->execute(['id' => $testData['student_id']]);
            }
            
            $this->db->prepare("DELETE FROM users WHERE id = :id")
                ->execute(['id' => $testData['user_id']]);
            $this->db->prepare("DELETE FROM users WHERE id = :id")
                ->execute(['id' => $testData['admin_id']]);
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
    $test = new CompleteViolationWorkflowTest();
    $test->run();
}
