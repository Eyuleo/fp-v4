<?php

/**
 * Webhook Payment Flow Test
 *
 * Tests webhook-based payment confirmation and order creation
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/PaymentService.php';
require_once __DIR__ . '/../src/Repositories/PaymentRepository.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';
require_once __DIR__ . '/../src/Repositories/ServiceRepository.php';

class WebhookPaymentFlowTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private PaymentService $paymentService;
    private PaymentRepository $paymentRepository;
    private OrderRepository $orderRepository;

    public function __construct()
    {
        $this->db                = getDatabaseConnection();
        $this->paymentRepository = new PaymentRepository($this->db);
        $this->paymentService    = new PaymentService($this->paymentRepository, $this->db);
        $this->orderRepository   = new OrderRepository($this->db);
    }

    public function run(): void
    {
        echo "Running Webhook Payment Flow Tests...\n\n";

        $this->testPaymentCreationStoresPendingOrderData();
        $this->testWebhookEventPersistence();
        $this->testPaymentStatusAfterWebhook();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testPaymentCreationStoresPendingOrderData(): void
    {
        echo "Test: Payment creation stores pending order data in metadata\n";

        // Simulate pending order in session
        $_SESSION['pending_order'] = [
            'client_id'    => 1,
            'service_id'   => 1,
            'requirements' => 'Test requirements',
            'files'        => [],
            'created_at'   => time(),
        ];

        // Create checkout session (without actually calling Stripe)
        // We'll verify the metadata structure instead
        $orderData = [
            'service_title'   => 'Test Service',
            'price'           => 100.00,
            'client_id'       => 1,
            'student_id'      => 2,
            'service_id'      => 1,
            'commission_rate' => 10.0,
        ];

        // Create payment record directly to test metadata storage
        $paymentId = $this->paymentRepository->create([
            'order_id'                   => null,
            'stripe_payment_intent_id'   => null,
            'stripe_checkout_session_id' => 'cs_test_' . time(),
            'amount'                     => 100.00,
            'commission_amount'          => 10.00,
            'student_amount'             => 90.00,
            'status'                     => 'pending',
            'metadata'                   => json_encode([
                'session_id'          => 'cs_test_' . time(),
                'commission_rate'     => 10.0,
                'created_at'          => date('Y-m-d H:i:s'),
                'has_real_order'      => false,
                'pending_order_data'  => $_SESSION['pending_order'],
            ]),
        ]);

        // Verify payment was created
        $this->assert($paymentId > 0, "Payment record created");

        // Verify metadata contains pending order data
        $payment = $this->paymentRepository->findById($paymentId);
        $metadata = json_decode($payment['metadata'], true);
        
        $this->assert(
            isset($metadata['pending_order_data']),
            "Metadata contains pending_order_data"
        );
        
        $this->assert(
            $metadata['pending_order_data']['requirements'] === 'Test requirements',
            "Pending order data preserved correctly"
        );

        // Cleanup
        $this->cleanupTestPayment($paymentId);
        unset($_SESSION['pending_order']);
    }

    private function testWebhookEventPersistence(): void
    {
        echo "\nTest: Webhook events are persisted to database\n";

        $eventId = 'evt_test_' . time();
        
        // Create webhook event
        $webhookId = $this->paymentRepository->createWebhookEvent([
            'stripe_event_id' => $eventId,
            'event_type'      => 'checkout.session.completed',
            'payload'         => json_encode(['test' => 'data']),
            'processed'       => false,
        ]);

        $this->assert($webhookId > 0, "Webhook event created");

        // Verify idempotency check works
        $exists = $this->paymentRepository->webhookEventExists($eventId);
        $this->assert($exists, "Webhook event exists check works");

        // Mark as processed
        $marked = $this->paymentRepository->markWebhookProcessed($eventId);
        $this->assert($marked, "Webhook marked as processed");

        // Cleanup
        $this->cleanupWebhookEvent($eventId);
    }

    private function testPaymentStatusAfterWebhook(): void
    {
        echo "\nTest: Payment status updates after webhook confirmation\n";

        $sessionId = 'cs_test_' . time();
        
        // Create pending payment
        $paymentId = $this->paymentRepository->create([
            'order_id'                   => null,
            'stripe_payment_intent_id'   => null,
            'stripe_checkout_session_id' => $sessionId,
            'amount'                     => 100.00,
            'commission_amount'          => 10.00,
            'student_amount'             => 90.00,
            'status'                     => 'pending',
            'metadata'                   => json_encode([
                'session_id'     => $sessionId,
                'created_at'     => date('Y-m-d H:i:s'),
                'has_real_order' => false,
            ]),
        ]);

        $this->assert($paymentId > 0, "Pending payment created");

        // Simulate webhook updating payment
        $updated = $this->paymentRepository->updateByCheckoutSession($sessionId, [
            'stripe_payment_intent_id' => 'pi_test_' . time(),
            'status'                   => 'succeeded',
            'metadata'                 => json_encode([
                'session_id'        => $sessionId,
                'payment_intent'    => 'pi_test_' . time(),
                'completed_at'      => date('Y-m-d H:i:s'),
                'webhook_confirmed' => true,
            ]),
        ]);

        $this->assert($updated, "Payment updated by webhook");

        // Verify payment status changed
        $payment = $this->paymentRepository->findByCheckoutSession($sessionId);
        $this->assert($payment['status'] === 'succeeded', "Payment status is 'succeeded'");
        
        $metadata = json_decode($payment['metadata'], true);
        $this->assert(
            isset($metadata['webhook_confirmed']) && $metadata['webhook_confirmed'] === true,
            "Webhook confirmation flag set"
        );

        // Cleanup
        $this->cleanupTestPayment($paymentId);
    }

    private function findById(int $id): ?array
    {
        $sql  = "SELECT * FROM payments WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $payment = $stmt->fetch();
        return $payment ?: null;
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

    private function cleanupWebhookEvent(string $eventId): void
    {
        try {
            $sql  = "DELETE FROM webhook_events WHERE stripe_event_id = :event_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['event_id' => $eventId]);
        } catch (Exception $e) {
            error_log('Failed to cleanup webhook event: ' . $e->getMessage());
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
    $test = new WebhookPaymentFlowTest();
    $test->run();
}
