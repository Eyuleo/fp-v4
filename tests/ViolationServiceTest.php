<?php

/**
 * Violation Service Test
 *
 * Basic tests for ViolationService functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/ViolationService.php';
require_once __DIR__ . '/../src/Repositories/ViolationRepository.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Repositories/MessageRepository.php';
require_once __DIR__ . '/../src/Services/NotificationService.php';
require_once __DIR__ . '/../src/Services/MailService.php';
require_once __DIR__ . '/../src/Repositories/NotificationRepository.php';

class ViolationServiceTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private ViolationService $violationService;
    private ViolationRepository $violationRepository;
    private UserRepository $userRepository;

    public function __construct()
    {
        // Use test database connection
        $this->db = getDatabaseConnection();
        $this->violationRepository = new ViolationRepository($this->db);
        $this->userRepository = new UserRepository($this->db);
        $messageRepository = new MessageRepository($this->db);
        $mailService = new MailService();
        $notificationRepository = new NotificationRepository($this->db);
        $notificationService = new NotificationService($mailService, $notificationRepository);
        
        $this->violationService = new ViolationService(
            $this->violationRepository,
            $this->userRepository,
            $messageRepository,
            $notificationService,
            $this->db
        );
    }

    public function run(): void
    {
        echo "Running Violation Service Tests...\n\n";

        $this->testCalculateSuggestedPenalty();
        $this->testConfirmViolationValidation();
        $this->testDismissFlag();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testCalculateSuggestedPenalty(): void
    {
        echo "Test: Calculate suggested penalty based on violation history\n";

        // Test with user who has no violations
        $penalty = $this->violationService->calculateSuggestedPenalty(99999);
        $this->assert($penalty === 'warning', "First violation suggests warning");

        // Note: Testing with actual violation counts would require creating test data
        // which is beyond the scope of this basic test
    }

    private function testConfirmViolationValidation(): void
    {
        echo "\nTest: Confirm violation validation\n";

        // Test with missing required fields
        $result = $this->violationService->confirmViolation(1, 1, []);
        
        $this->assert(!$result['success'], "Validation fails with missing fields");
        $this->assert(isset($result['errors']['violation_type']), "Error for missing violation_type");
        $this->assert(isset($result['errors']['severity']), "Error for missing severity");
        $this->assert(isset($result['errors']['penalty_type']), "Error for missing penalty_type");
    }

    private function testDismissFlag(): void
    {
        echo "\nTest: Dismiss flag functionality\n";

        // Create a test flagged message
        $testMessageId = $this->createTestMessage();

        if ($testMessageId) {
            // Dismiss the flag
            $result = $this->violationService->dismissFlag($testMessageId, 1);
            
            $this->assert($result['success'], "Flag dismissal succeeds");
            $this->assert(empty($result['errors']), "No errors returned");

            // Verify message is no longer flagged
            $sql = "SELECT is_flagged FROM messages WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $testMessageId]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->assert($message && !$message['is_flagged'], "Message is no longer flagged");

            // Cleanup
            $this->cleanupTestMessage($testMessageId);
        } else {
            echo "  ⚠ WARNING: Could not create test message for dismiss flag test\n";
        }
    }

    private function createTestMessage(): ?int
    {
        try {
            $sql = "INSERT INTO messages (order_id, sender_id, content, is_flagged, created_at)
                    VALUES (1, 1, 'Test message', TRUE, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return (int) $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Failed to create test message: ' . $e->getMessage());
            return null;
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
    $test = new ViolationServiceTest();
    $test->run();
}
