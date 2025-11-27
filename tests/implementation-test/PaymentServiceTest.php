<?php

/**
 * Payment Service Test
 *
 * Tests for PaymentService refund functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';
require_once __DIR__ . '/../src/Repositories/PaymentRepository.php';

class PaymentServiceTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private PaymentService $paymentService;
    private PaymentRepository $paymentRepository;

    public function __construct()
    {
        // Use test database connection
        $this->db                = getDatabaseConnection();
        $this->paymentRepository = new PaymentRepository($this->db);
        $this->paymentService    = new PaymentService($this->paymentRepository, $this->db);
    }

    public function run(): void
    {
        echo "Running Payment Service Tests...\n\n";

        $this->testRefundErrorLogging();
        $this->testRefundTransactionRollback();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testRefundErrorLogging(): void
    {
        echo "Test: Refund error logging format\n";

        // Create test order and payment
        $order = [
            'id'         => 99999,
            'client_id'  => 1,
            'student_id' => 2,
            'status'     => 'in_progress',
        ];

        // Test with non-existent payment (should log error)
        $result = $this->paymentService->refundPayment($order);

        // Check that refund failed
        $this->assert(! $result['success'], "Refund fails when payment not found");
        $this->assert(isset($result['errors']['payment']), "Error message is returned");

        // Verify error was logged (check error log file)
        $logContent = @file_get_contents(__DIR__ . '/../logs/error.log');
        if ($logContent !== false) {
            $hasJsonLog = strpos($logContent, '"error":"Payment not found"') !== false;
            $this->assert($hasJsonLog, "Error is logged in JSON format");
        } else {
            echo "  ⚠ WARNING: Could not verify error log file\n";
        }
    }

    private function testRefundTransactionRollback(): void
    {
        echo "\nTest: Transaction rollback on refund failure\n";

        // This test verifies that database state is not changed if refund fails
        // In a real scenario, we would mock Stripe to force a failure after DB update

        // Create a test payment record
        $testPaymentId = $this->createTestPayment();

        if ($testPaymentId) {
            // Get initial payment state
            $initialPayment = $this->paymentRepository->findById($testPaymentId);

            // Attempt refund with invalid Stripe payment intent (will fail)
            $order = [
                'id'         => $initialPayment['order_id'],
                'client_id'  => 1,
                'student_id' => 2,
                'status'     => 'in_progress',
            ];

            $result = $this->paymentService->refundPayment($order);

            // Get payment state after failed refund
            $afterPayment = $this->paymentRepository->findById($testPaymentId);

            // Verify payment status hasn't changed
            $this->assert(
                $initialPayment['status'] === $afterPayment['status'],
                "Payment status unchanged after failed refund"
            );

            $this->assert(
                $initialPayment['refund_amount'] === $afterPayment['refund_amount'],
                "Refund amount unchanged after failed refund"
            );

            // Cleanup
            $this->cleanupTestPayment($testPaymentId);
        } else {
            echo "  ⚠ WARNING: Could not create test payment for rollback test\n";
        }
    }

    private function createTestPayment(): ?int
    {
        try {
            // Create a test payment with invalid Stripe payment intent
            $paymentId = $this->paymentRepository->create([
                'order_id'                   => 99998,
                'stripe_payment_intent_id'   => 'pi_test_invalid_' . time(),
                'stripe_checkout_session_id' => 'cs_test_' . time(),
                'amount'                     => 100.00,
                'commission_amount'          => 10.00,
                'student_amount'             => 90.00,
                'status'                     => 'succeeded',
                'metadata'                   => json_encode(['test' => true]),
            ]);

            return $paymentId;
        } catch (Exception $e) {
            error_log('Failed to create test payment: ' . $e->getMessage());

            return null;
        }
    }

    private function cleanupTestPayment(int $paymentId): void
    {
        try {
            $sql  = "DELETE FROM payments WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $paymentId]);
        } catch (Exception $e) {
            error_log('Failed to cleanup test payment: ' . $e->getMessage());
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
    $test = new PaymentServiceTest();
    $test->run();
}
