<?php

/**
 * Complete Dispute Workflow Test
 *
 * Integration test for the complete dispute workflow including:
 * - Dispute creation by clients and students
 * - All three resolution types (release_to_student, refund_to_client, partial_refund)
 * - Payment/refund processing
 * - Notification sending to all parties
 * - Authorization at each step
 *
 * Requirements: 6.1, 6.2, 8.4, 8.5, 9.2, 9.3, 9.5, 10.1, 11.1, 11.2, 11.4
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/DisputeService.php';
require_once __DIR__ . '/../src/Services/OrderService.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';
require_once __DIR__ . '/../src/Repositories/DisputeRepository.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';
require_once __DIR__ . '/../src/Repositories/PaymentRepository.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Repositories/ServiceRepository.php';
require_once __DIR__ . '/../src/Services/NotificationService.php';
require_once __DIR__ . '/../src/Services/MailService.php';
require_once __DIR__ . '/../src/Repositories/NotificationRepository.php';

class CompleteDisputeWorkflowTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private DisputeService $disputeService;
    private OrderService $orderService;
    private PaymentService $paymentService;
    private DisputeRepository $disputeRepository;
    private OrderRepository $orderRepository;
    private PaymentRepository $paymentRepository;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
        
        // Initialize repositories
        $this->disputeRepository = new DisputeRepository($this->db);
        $this->orderRepository = new OrderRepository($this->db);
        $this->paymentRepository = new PaymentRepository($this->db);
        $userRepository = new UserRepository($this->db);
        $serviceRepository = new ServiceRepository($this->db);
        $notificationRepository = new NotificationRepository($this->db);
        
        // Initialize services
        $mailService = new MailService();
        $notificationService = new NotificationService($mailService, $notificationRepository);
        $this->paymentService = new PaymentService($this->paymentRepository, $this->db);
        $this->orderService = new OrderService($this->orderRepository, $serviceRepository, $this->paymentService);
        
        $this->disputeService = new DisputeService($this->db);
    }

    public function run(): void
    {
        echo "Running Complete Dispute Workflow Tests...\n\n";

        $this->testDisputeCreationByClient();
        $this->testDisputeCreationByStudent();
        $this->testDisputeCreationAuthorization();
        $this->testReleaseToStudentResolution();
        $this->testRefundToClientResolution();
        $this->testPartialRefundResolution();
        $this->testResolutionNotifications();
        $this->testResolutionAuthorization();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    /**
     * Test dispute creation by client
     * Requirements: 6.1, 6.2
     */
    private function testDisputeCreationByClient(): void
    {
        echo "Test: Dispute creation by client\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Client creates dispute
        $result = $this->disputeService->createDispute(
            $testData['client_id'],
            $testData['order_id'],
            'Work does not meet requirements'
        );

        $this->assert($result['success'], "Client can create dispute");
        $this->assert(isset($result['dispute_id']), "Dispute ID returned");

        // Verify dispute record (Requirement 6.1)
        $dispute = $this->disputeRepository->findById($result['dispute_id']);
        $this->assert($dispute !== null, "Dispute record created");
        $this->assert($dispute['order_id'] == $testData['order_id'], "Dispute linked to order");
        $this->assert($dispute['opened_by'] == $testData['client_id'], "Dispute opened by client");
        $this->assert($dispute['status'] === 'open', "Dispute status is open");
        $this->assert(!empty($dispute['reason']), "Dispute reason recorded");

        // Verify notifications sent
        $notifications = $this->getNotificationsForUser($testData['student_id']);
        $this->assert(count($notifications) > 0, "Student notified of dispute");

        $this->cleanupTestData($testData);
    }

    /**
     * Test dispute creation by student
     * Requirements: 6.1, 6.2
     */
    private function testDisputeCreationByStudent(): void
    {
        echo "\nTest: Dispute creation by student\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Student creates dispute
        $result = $this->disputeService->createDispute(
            $testData['student_id'],
            $testData['order_id'],
            'Client is unreasonable with revision requests'
        );

        $this->assert($result['success'], "Student can create dispute");
        $this->assert(isset($result['dispute_id']), "Dispute ID returned");

        // Verify dispute record (Requirement 6.1)
        $dispute = $this->disputeRepository->findById($result['dispute_id']);
        $this->assert($dispute !== null, "Dispute record created");
        $this->assert($dispute['opened_by'] == $testData['student_id'], "Dispute opened by student");

        // Verify notifications sent
        $notifications = $this->getNotificationsForUser($testData['client_id']);
        $this->assert(count($notifications) > 0, "Client notified of dispute");

        $this->cleanupTestData($testData);
    }

    /**
     * Test dispute creation authorization
     * Requirements: 6.2, 11.1, 11.2
     */
    private function testDisputeCreationAuthorization(): void
    {
        echo "\nTest: Dispute creation authorization\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Create unauthorized user
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
            VALUES (:email, :password, 'client', 'active', NOW(), NOW())
        ");
        $stmt->execute([
            'email' => 'unauthorized_' . time() . '@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT)
        ]);
        $unauthorizedUserId = (int) $this->db->lastInsertId();

        // Unauthorized user attempts to create dispute (Requirement 6.2)
        $result = $this->disputeService->createDispute(
            $unauthorizedUserId,
            $testData['order_id'],
            'Unauthorized dispute attempt'
        );

        $this->assert(!$result['success'], "Unauthorized user cannot create dispute");
        $this->assert(isset($result['errors']['authorization']), "Authorization error returned");

        // Test duplicate dispute prevention (Requirement 6.4)
        $this->disputeService->createDispute(
            $testData['client_id'],
            $testData['order_id'],
            'First dispute'
        );

        $result2 = $this->disputeService->createDispute(
            $testData['client_id'],
            $testData['order_id'],
            'Second dispute attempt'
        );

        $this->assert(!$result2['success'], "Cannot create duplicate dispute");
        $this->assert(
            isset($result2['errors']['duplicate']),
            "Duplicate error returned"
        );

        // Cleanup
        $this->db->prepare("DELETE FROM users WHERE id = :id")
            ->execute(['id' => $unauthorizedUserId]);
        $this->cleanupTestData($testData);
    }

    /**
     * Test release to student resolution
     * Requirements: 8.4, 9.2, 10.1
     */
    private function testReleaseToStudentResolution(): void
    {
        echo "\nTest: Release to student resolution\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Create dispute
        $disputeResult = $this->disputeService->createDispute(
            $testData['client_id'],
            $testData['order_id'],
            'Test dispute for release resolution'
        );
        $disputeId = $disputeResult['dispute_id'];

        // Admin resolves with release_to_student (Requirement 8.4)
        $result = $this->disputeService->resolveDispute(
            $disputeId,
            $testData['admin_id'],
            [
                'resolution' => 'release_to_student',
                'resolution_notes' => 'Work meets requirements, releasing payment to student'
            ]
        );

        $this->assert($result['success'], "Resolution succeeds");

        // Verify dispute updated
        $dispute = $this->disputeRepository->findById($disputeId);
        $this->assert($dispute['status'] === 'resolved', "Dispute status is resolved");
        $this->assert($dispute['resolution'] === 'release_to_student', "Resolution type recorded");
        $this->assert($dispute['resolved_by'] == $testData['admin_id'], "Resolved by admin");
        $this->assert($dispute['resolved_at'] !== null, "Resolution timestamp recorded");

        // Verify order status updated (Requirement 8.4)
        $order = $this->orderRepository->findById($testData['order_id']);
        $this->assert($order['status'] === 'completed', "Order status is completed");

        // Verify payment processed (Requirement 8.4)
        $payments = $this->paymentRepository->findByOrderId($testData['order_id']);
        $this->assert($payments !== null, "Payments retrieved");
        
        if ($payments) {
            $studentPayment = array_filter($payments, function($p) use ($testData) {
                return $p['student_id'] == $testData['student_id'] && $p['type'] === 'payout';
            });
            $this->assert(count($studentPayment) > 0, "Student payment recorded");
        }

        // Verify notifications sent (Requirement 10.1)
        $studentNotifications = $this->getNotificationsForUser($testData['student_id']);
        $clientNotifications = $this->getNotificationsForUser($testData['client_id']);
        $this->assert(count($studentNotifications) > 0, "Student notified of resolution");
        $this->assert(count($clientNotifications) > 0, "Client notified of resolution");

        $this->cleanupTestData($testData);
    }

    /**
     * Test refund to client resolution
     * Requirements: 8.5, 9.2, 10.1
     */
    private function testRefundToClientResolution(): void
    {
        echo "\nTest: Refund to client resolution\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Create dispute
        $disputeResult = $this->disputeService->createDispute(
            $testData['client_id'],
            $testData['order_id'],
            'Work is completely unacceptable'
        );
        $disputeId = $disputeResult['dispute_id'];

        // Admin resolves with refund_to_client (Requirement 8.5)
        $result = $this->disputeService->resolveDispute(
            $disputeId,
            $testData['admin_id'],
            [
                'resolution' => 'refund_to_client',
                'resolution_notes' => 'Work does not meet requirements, full refund issued'
            ]
        );

        $this->assert($result['success'], "Resolution succeeds");

        // Verify dispute updated
        $dispute = $this->disputeRepository->findById($disputeId);
        $this->assert($dispute['status'] === 'resolved', "Dispute status is resolved");
        $this->assert($dispute['resolution'] === 'refund_to_client', "Resolution type recorded");

        // Verify order status updated (Requirement 8.5)
        $order = $this->orderRepository->findById($testData['order_id']);
        $this->assert($order['status'] === 'cancelled', "Order status is cancelled");

        // Verify refund processed (Requirement 8.5)
        $payments = $this->paymentRepository->findByOrderId($testData['order_id']);
        $this->assert($payments !== null, "Payments retrieved");
        
        if ($payments) {
            $refund = array_filter($payments, function($p) use ($testData) {
                return $p['client_id'] == $testData['client_id'] && $p['type'] === 'refund';
            });
            $this->assert(count($refund) > 0, "Refund recorded");
        }

        // Verify notifications sent (Requirement 10.1)
        $studentNotifications = $this->getNotificationsForUser($testData['student_id']);
        $clientNotifications = $this->getNotificationsForUser($testData['client_id']);
        $this->assert(count($studentNotifications) > 0, "Student notified of resolution");
        $this->assert(count($clientNotifications) > 0, "Client notified of resolution");

        $this->cleanupTestData($testData);
    }

    /**
     * Test partial refund resolution
     * Requirements: 9.2, 9.3, 9.5, 10.1
     */
    private function testPartialRefundResolution(): void
    {
        echo "\nTest: Partial refund resolution\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Create dispute
        $disputeResult = $this->disputeService->createDispute(
            $testData['client_id'],
            $testData['order_id'],
            'Work partially meets requirements'
        );
        $disputeId = $disputeResult['dispute_id'];

        // Get order details for calculation verification
        $order = $this->orderRepository->findById($testData['order_id']);
        $orderAmount = $order['price'];
        $refundPercentage = 40.0;

        // Admin resolves with partial_refund
        $result = $this->disputeService->resolveDispute(
            $disputeId,
            $testData['admin_id'],
            [
                'resolution' => 'partial_refund',
                'refund_percentage' => $refundPercentage,
                'resolution_notes' => 'Work partially acceptable, 40% refund to client, 60% to student'
            ]
        );

        $this->assert($result['success'], "Resolution succeeds");

        // Verify dispute updated
        $dispute = $this->disputeRepository->findById($disputeId);
        $this->assert($dispute['status'] === 'resolved', "Dispute status is resolved");
        $this->assert($dispute['resolution'] === 'partial_refund', "Resolution type recorded");
        $this->assert($dispute['refund_percentage'] == $refundPercentage, "Refund percentage recorded");

        // Verify order status updated (Requirement 9.4)
        $order = $this->orderRepository->findById($testData['order_id']);
        $this->assert($order['status'] === 'completed', "Order status is completed");

        // Verify payment calculations (Requirements 9.2, 9.3)
        $expectedRefund = $orderAmount * ($refundPercentage / 100);
        $expectedStudentPayment = $orderAmount - $expectedRefund;

        $payments = $this->paymentRepository->findByOrderId($testData['order_id']);
        $this->assert($payments !== null, "Payments retrieved");
        
        if ($payments) {
            // Find refund transaction (Requirement 9.5)
            $refund = array_filter($payments, function($p) use ($testData) {
                return $p['client_id'] == $testData['client_id'] && $p['type'] === 'refund';
            });
            $this->assert(count($refund) > 0, "Refund transaction recorded");
            
            if (count($refund) > 0) {
                $refundRecord = array_values($refund)[0];
                $this->assert(
                    abs($refundRecord['amount'] - $expectedRefund) < 0.01,
                    "Refund amount calculated correctly (expected: $expectedRefund, got: {$refundRecord['amount']})"
                );
            }

            // Find student payment transaction (Requirement 9.5)
            $studentPayment = array_filter($payments, function($p) use ($testData) {
                return $p['student_id'] == $testData['student_id'] && $p['type'] === 'payout';
            });
            $this->assert(count($studentPayment) > 0, "Student payment transaction recorded");
            
            if (count($studentPayment) > 0) {
                $paymentRecord = array_values($studentPayment)[0];
                $this->assert(
                    abs($paymentRecord['amount'] - $expectedStudentPayment) < 0.01,
                    "Student payment calculated correctly (expected: $expectedStudentPayment, got: {$paymentRecord['amount']})"
                );
            }
        }

        // Verify notifications sent (Requirement 10.1)
        $studentNotifications = $this->getNotificationsForUser($testData['student_id']);
        $clientNotifications = $this->getNotificationsForUser($testData['client_id']);
        $this->assert(count($studentNotifications) > 0, "Student notified of resolution");
        $this->assert(count($clientNotifications) > 0, "Client notified of resolution");

        $this->cleanupTestData($testData);
    }

    /**
     * Test resolution notifications
     * Requirement: 10.1
     */
    private function testResolutionNotifications(): void
    {
        echo "\nTest: Resolution notifications to all parties\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Create and resolve dispute
        $disputeResult = $this->disputeService->createDispute(
            $testData['client_id'],
            $testData['order_id'],
            'Test dispute for notifications'
        );

        $this->disputeService->resolveDispute(
            $disputeResult['dispute_id'],
            $testData['admin_id'],
            [
                'resolution' => 'release_to_student',
                'resolution_notes' => 'Testing notifications'
            ]
        );

        // Verify both parties received notifications (Requirement 10.1)
        $studentNotifications = $this->getNotificationsForUser($testData['student_id']);
        $clientNotifications = $this->getNotificationsForUser($testData['client_id']);

        $this->assert(count($studentNotifications) >= 2, "Student received creation and resolution notifications");
        $this->assert(count($clientNotifications) >= 2, "Client received creation and resolution notifications");

        // Verify notification content includes resolution details
        $resolutionNotification = end($studentNotifications);
        $this->assert(
            strpos($resolutionNotification['message'], 'resolved') !== false ||
            strpos($resolutionNotification['message'], 'resolution') !== false,
            "Notification mentions resolution"
        );

        $this->cleanupTestData($testData);
    }

    /**
     * Test resolution authorization
     * Requirements: 11.1, 11.4
     */
    private function testResolutionAuthorization(): void
    {
        echo "\nTest: Resolution authorization (admin only)\n";

        $testData = $this->createTestData();
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Create dispute
        $disputeResult = $this->disputeService->createDispute(
            $testData['client_id'],
            $testData['order_id'],
            'Test dispute for authorization'
        );
        $disputeId = $disputeResult['dispute_id'];

        // Non-admin (client) attempts to resolve (Requirement 11.4)
        $result = $this->disputeService->resolveDispute(
            $disputeId,
            $testData['client_id'],
            [
                'resolution' => 'release_to_student',
                'resolution_notes' => 'Unauthorized resolution attempt'
            ]
        );

        $this->assert(!$result['success'], "Non-admin cannot resolve dispute");
        $this->assert(
            isset($result['errors']['authorization']),
            "Authorization error returned"
        );

        // Verify dispute remains unresolved
        $dispute = $this->disputeRepository->findById($disputeId);
        $this->assert($dispute['status'] === 'open', "Dispute remains open");
        $this->assert($dispute['resolved_by'] === null, "No resolver recorded");

        $this->cleanupTestData($testData);
    }

    // Helper methods

    private function createTestData(): ?array
    {
        try {
            $this->db->beginTransaction();

            // Create admin
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'admin', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'test_admin_' . time() . rand(1000, 9999) . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
            $adminId = (int) $this->db->lastInsertId();

            // Create client
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'client', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'test_client_' . time() . rand(1000, 9999) . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
            $clientId = (int) $this->db->lastInsertId();

            // Create student
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
                VALUES (:service_id, :client_id, :student_id, 100.00, 15.00, 'Test', DATE_ADD(NOW(), INTERVAL 7 DAY), 'delivered', NOW(), NOW())
            ");
            $stmt->execute([
                'service_id' => $serviceId,
                'client_id' => $clientId,
                'student_id' => $studentId
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'admin_id' => $adminId,
                'client_id' => $clientId,
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

    private function cleanupTestData(?array $testData): void
    {
        if (!$testData) {
            return;
        }

        try {
            // Delete in reverse order of creation
            if (isset($testData['order_id'])) {
                $this->db->prepare("DELETE FROM payments WHERE order_id = :order_id")
                    ->execute(['order_id' => $testData['order_id']]);
                $this->db->prepare("DELETE FROM disputes WHERE order_id = :order_id")
                    ->execute(['order_id' => $testData['order_id']]);
                $this->db->prepare("DELETE FROM orders WHERE id = :id")
                    ->execute(['id' => $testData['order_id']]);
            }
            
            if (isset($testData['service_id'])) {
                $this->db->prepare("DELETE FROM services WHERE id = :id")
                    ->execute(['id' => $testData['service_id']]);
            }
            
            if (isset($testData['student_id'])) {
                $this->db->prepare("DELETE FROM notifications WHERE user_id = :user_id")
                    ->execute(['user_id' => $testData['student_id']]);
                $this->db->prepare("DELETE FROM student_profiles WHERE user_id = :user_id")
                    ->execute(['user_id' => $testData['student_id']]);
                $this->db->prepare("DELETE FROM users WHERE id = :id")
                    ->execute(['id' => $testData['student_id']]);
            }
            
            if (isset($testData['client_id'])) {
                $this->db->prepare("DELETE FROM notifications WHERE user_id = :user_id")
                    ->execute(['user_id' => $testData['client_id']]);
                $this->db->prepare("DELETE FROM users WHERE id = :id")
                    ->execute(['id' => $testData['client_id']]);
            }
            
            if (isset($testData['admin_id'])) {
                $this->db->prepare("DELETE FROM users WHERE id = :id")
                    ->execute(['id' => $testData['admin_id']]);
            }
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
    $test = new CompleteDisputeWorkflowTest();
    $test->run();
}
