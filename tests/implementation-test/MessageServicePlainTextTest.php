<?php

/**
 * Message Service Plain Text Test
 *
 * Tests for MessageService plain text messaging functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Services/MessageService.php';
require_once __DIR__ . '/../src/Repositories/MessageRepository.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';

class MessageServicePlainTextTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private MessageService $messageService;
    private MessageRepository $messageRepository;
    private OrderRepository $orderRepository;

    public function __construct()
    {
        $this->db                = getDatabaseConnection();
        $this->messageRepository = new MessageRepository($this->db);
        $this->orderRepository   = new OrderRepository($this->db);
        $this->messageService    = new MessageService($this->messageRepository, $this->orderRepository);
    }

    public function run(): void
    {
        echo "Running Message Service Plain Text Tests...\n\n";

        $this->testPlainTextMessageAcceptance();
        $this->testEmptyMessageRejection();
        $this->testMessageWithAttachments();
        $this->testMessageWithTextAndAttachments();
        $this->testEmptyAttachmentArrayHandling();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testPlainTextMessageAcceptance(): void
    {
        echo "Test: Plain text message without attachments\n";

        // Create test order
        $orderId = $this->createTestOrder();

        if ($orderId) {
            // Get the order to find valid sender ID
            $order = $this->orderRepository->findById($orderId);
            
            // Send plain text message (no attachments)
            $result = $this->messageService->sendMessage(
                senderId: $order['client_id'],
                orderId: $orderId,
                content: 'This is a plain text message',
                attachments: []
            );

            $this->assert($result['success'], "Plain text message is accepted");
            $this->assert(isset($result['message_id']), "Message ID is returned");
            $this->assert(empty($result['errors']), "No errors are returned");

            // Verify message was stored correctly
            if ($result['success'] && isset($result['message_id'])) {
                $message = $this->messageRepository->findById($result['message_id']);
                $this->assert($message !== null, "Message is stored in database");
                $this->assert($message['content'] === 'This is a plain text message', "Message content is correct");
                $this->assert(empty($message['attachments']), "Attachments array is empty");

                // Cleanup
                $this->cleanupTestMessage($result['message_id']);
            }

            $this->cleanupTestOrder($orderId);
        } else {
            echo "  ⚠ WARNING: Could not create test order\n";
        }
    }

    private function testEmptyMessageRejection(): void
    {
        echo "\nTest: Empty message rejection\n";

        $orderId = $this->createTestOrder();

        if ($orderId) {
            // Get the order to find valid sender ID
            $order = $this->orderRepository->findById($orderId);
            
            // Try to send empty message
            $result = $this->messageService->sendMessage(
                senderId: $order['client_id'],
                orderId: $orderId,
                content: '',
                attachments: []
            );

            $this->assert(!$result['success'], "Empty message is rejected");
            $this->assert(isset($result['errors']['content']), "Error message is returned");

            $this->cleanupTestOrder($orderId);
        } else {
            echo "  ⚠ WARNING: Could not create test order\n";
        }
    }

    private function testMessageWithAttachments(): void
    {
        echo "\nTest: Message with attachments (backward compatibility)\n";

        $orderId = $this->createTestOrder();

        if ($orderId) {
            // Get the order to find valid sender ID
            $order = $this->orderRepository->findById($orderId);
            
            // Simulate message with attachments (already uploaded)
            $attachments = [
                [
                    'path' => 'storage/uploads/messages/1/test.pdf',
                    'original_name' => 'test.pdf',
                    'size' => 1024
                ]
            ];

            $result = $this->messageService->sendMessage(
                senderId: $order['client_id'],
                orderId: $orderId,
                content: 'Message with attachment',
                attachments: $attachments
            );

            $this->assert($result['success'], "Message with attachments is accepted");

            if ($result['success'] && isset($result['message_id'])) {
                $message = $this->messageRepository->findById($result['message_id']);
                $this->assert(!empty($message['attachments']), "Attachments are stored");

                $this->cleanupTestMessage($result['message_id']);
            }

            $this->cleanupTestOrder($orderId);
        } else {
            echo "  ⚠ WARNING: Could not create test order\n";
        }
    }

    private function testMessageWithTextAndAttachments(): void
    {
        echo "\nTest: Message with both text and attachments\n";

        $orderId = $this->createTestOrder();

        if ($orderId) {
            // Get the order to find valid sender ID
            $order = $this->orderRepository->findById($orderId);
            
            $attachments = [
                [
                    'path' => 'storage/uploads/messages/1/test.pdf',
                    'original_name' => 'test.pdf',
                    'size' => 1024
                ]
            ];

            $result = $this->messageService->sendMessage(
                senderId: $order['client_id'],
                orderId: $orderId,
                content: 'Here is the file you requested',
                attachments: $attachments
            );

            $this->assert($result['success'], "Message with text and attachments is accepted");

            if ($result['success'] && isset($result['message_id'])) {
                $message = $this->messageRepository->findById($result['message_id']);
                $this->assert($message['content'] === 'Here is the file you requested', "Text content is stored");
                $this->assert(!empty($message['attachments']), "Attachments are stored");

                $this->cleanupTestMessage($result['message_id']);
            }

            $this->cleanupTestOrder($orderId);
        } else {
            echo "  ⚠ WARNING: Could not create test order\n";
        }
    }

    private function testEmptyAttachmentArrayHandling(): void
    {
        echo "\nTest: Empty attachment array handling\n";

        $orderId = $this->createTestOrder();

        if ($orderId) {
            // Get the order to find valid sender ID
            $order = $this->orderRepository->findById($orderId);
            
            // Test with explicit empty array
            $result = $this->messageService->sendMessage(
                senderId: $order['client_id'],
                orderId: $orderId,
                content: 'Message with empty attachment array',
                attachments: []
            );

            $this->assert($result['success'], "Message with empty attachment array is accepted");

            if ($result['success'] && isset($result['message_id'])) {
                $message = $this->messageRepository->findById($result['message_id']);
                $this->assert(empty($message['attachments']), "Empty attachments array is stored correctly");

                $this->cleanupTestMessage($result['message_id']);
            }

            $this->cleanupTestOrder($orderId);
        } else {
            echo "  ⚠ WARNING: Could not create test order\n";
        }
    }

    private function createTestOrder(): ?int
    {
        try {
            // First, get valid user IDs
            $clientStmt = $this->db->query("SELECT id FROM users WHERE role = 'client' LIMIT 1");
            $client = $clientStmt->fetch();
            
            $studentStmt = $this->db->query("SELECT id FROM users WHERE role = 'student' LIMIT 1");
            $student = $studentStmt->fetch();
            
            $serviceStmt = $this->db->query("SELECT id FROM services LIMIT 1");
            $service = $serviceStmt->fetch();
            
            if (!$client || !$student || !$service) {
                error_log('Missing required data: client, student, or service');
                return null;
            }
            
            $sql = "INSERT INTO orders (client_id, student_id, service_id, status, price, commission_rate, deadline, created_at)
                    VALUES (:client_id, :student_id, :service_id, 'in_progress', 100.00, 10.00, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'client_id' => $client['id'],
                'student_id' => $student['id'],
                'service_id' => $service['id']
            ]);

            return (int) $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Failed to create test order: ' . $e->getMessage());
            return null;
        }
    }

    private function cleanupTestOrder(int $orderId): void
    {
        try {
            $sql = "DELETE FROM orders WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $orderId]);
        } catch (Exception $e) {
            error_log('Failed to cleanup test order: ' . $e->getMessage());
        }
    }

    private function cleanupTestMessage(int $messageId): void
    {
        try {
            $sql = "DELETE FROM messages WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $messageId]);
        } catch (Exception $e) {
            error_log('Failed to cleanup test message: ' . $e->getMessage());
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
    $test = new MessageServicePlainTextTest();
    $test->run();
}
