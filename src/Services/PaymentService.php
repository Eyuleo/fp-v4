<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Repositories/PaymentRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';
require_once __DIR__ . '/WithdrawalService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/MailService.php';

/**
 * Payment Service
 *
 * Handles payment processing with Stripe
 */
class PaymentService
{
    private PaymentRepository $repository;
    private WithdrawalService $withdrawalService;
    private NotificationService $notificationService;
    private UserRepository $userRepository;
    private ServiceRepository $serviceRepository;
    private OrderRepository $orderRepository;
    private PDO $db;
    private string $stripeSecretKey;
    private string $stripeWebhookSecret;

    public function __construct(PaymentRepository $repository, PDO $db)
    {
        $this->repository          = $repository;
        $this->db                  = $db;
        $this->withdrawalService   = new WithdrawalService($db);
        $this->userRepository      = new UserRepository($db);
        $this->serviceRepository   = new ServiceRepository($db);
        $this->orderRepository     = new OrderRepository($db);
        $mailService               = new MailService();
        $notificationRepository    = new NotificationRepository($db);
        $this->notificationService = new NotificationService($mailService, $notificationRepository);
        $this->stripeSecretKey     = getenv('STRIPE_SECRET_KEY') ?: '';
        $this->stripeWebhookSecret = getenv('STRIPE_WEBHOOK_SECRET') ?: '';

        // Set Stripe API key
        \Stripe\Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Create Stripe checkout session for an order
     *
     * @param array $order Order data
     * @param string $successUrl URL to redirect on success
     * @param string $cancelUrl URL to redirect on cancel
     * @return array ['success' => bool, 'session_url' => string|null, 'session_id' => string|null, 'payment_id' => int|null, 'errors' => array]
     */
    public function createCheckoutSession(array $order, string $successUrl, string $cancelUrl): array
    {
        try {
            // Validate essential fields
            foreach (['service_title', 'price', 'client_id', 'student_id'] as $required) {
                if (! array_key_exists($required, $order)) {
                    throw new Exception("Missing required field: {$required}");
                }
            }

            $price          = (float) $order['price'];
            $commissionRate = isset($order['commission_rate']) ? (float) $order['commission_rate'] : 0.0;

            // Use a numeric order id if available; otherwise null (will be linked later)
            $orderIdForPayment = (isset($order['id']) && is_numeric($order['id'])) ? (int) $order['id'] : null;

            // Create checkout session
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => 'usd',
                        'product_data' => [
                            'name'        => $order['service_title'],
                            'description' => 'Order ' . ($orderIdForPayment ?? 'pending'),
                        ],
                        'unit_amount'  => (int) round($price * 100), // Convert to cents
                    ],
                    'quantity'   => 1,
                ]],
                'mode'                 => 'payment',
                'success_url'          => $successUrl,
                'cancel_url'           => $cancelUrl,
                'metadata'             => [
                    'order_id'   => $orderIdForPayment ?? '', // may be blank if order not yet created
                    'client_id'  => $order['client_id'],
                    'student_id' => $order['student_id'],
                ],
            ]);

            // Calculate commission and student amount
            $commissionAmount = $price * ($commissionRate / 100);
            $studentAmount    = $price - $commissionAmount;

            // Create payment record with pending status
            $paymentId = $this->repository->create([
                'order_id'                   => $orderIdForPayment, // may be NULL and linked later
                'stripe_payment_intent_id'   => null,               // Will be updated after success (no webhook path)
                'stripe_checkout_session_id' => $session->id,
                'amount'                     => $price,
                'commission_amount'          => $commissionAmount,
                'student_amount'             => $studentAmount,
                'status'                     => 'pending',
                'metadata'                   => json_encode([
                    'session_id'      => $session->id,
                    'commission_rate' => $commissionRate,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'has_real_order'  => $orderIdForPayment !== null,
                ]),
            ]);

            return [
                'success'     => true,
                'session_url' => $session->url,
                'session_id'  => $session->id,
                'payment_id'  => $paymentId,
                'errors'      => [],
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API error: ' . $e->getMessage());

            return [
                'success'     => false,
                'session_url' => null,
                'session_id'  => null,
                'payment_id'  => null,
                'errors'      => ['payment' => 'Payment processing failed. Please try again.'],
            ];
        } catch (Exception $e) {
            error_log('Payment service error: ' . $e->getMessage());

            return [
                'success'     => false,
                'session_url' => null,
                'session_id'  => null,
                'payment_id'  => null,
                'errors'      => ['payment' => 'An error occurred. Please try again.'],
            ];
        }
    }

    /**
     * Finalize payment without using Stripe webhooks.
     * Retrieves the Checkout Session, extracts payment_intent, and updates payment row.
     *
     * @param int    $paymentId
     * @param string $stripeSessionId
     * @param int    $orderId
     * @return array
     */
    public function finalizePaymentWithoutWebhook(int $paymentId, string $stripeSessionId, int $orderId): array
    {
        try {
            $session = \Stripe\Checkout\Session::retrieve($stripeSessionId);

            if (! $session) {
                throw new Exception('Stripe session not found for finalization');
            }

            if ($session->payment_status !== 'paid') {
                throw new Exception('Stripe session not in paid state: ' . $session->payment_status);
            }

            $paymentIntentId = $session->payment_intent ?? null;
            if (! $paymentIntentId) {
                throw new Exception('Payment intent missing on session');
            }

            // Fetch existing payment row
            $existing = $this->repositoryPaymentById($paymentId);
            if (! $existing) {
                throw new Exception('Local payment record not found');
            }

            // Only update if still pending
            if ($existing['status'] !== 'pending') {
                return ['success' => true, 'updated' => false, 'reason' => 'Payment already finalized'];
            }

            $metadata = [
                'session_id'        => $stripeSessionId,
                'payment_intent'    => $paymentIntentId,
                'finalized_at'      => date('Y-m-d H:i:s'),
                'finalization_mode' => 'no_webhook',
            ];

            $this->repository->update($paymentId, [
                'order_id'                 => $orderId,
                'stripe_payment_intent_id' => $paymentIntentId,
                'status'                   => 'succeeded',
                'metadata'                 => json_encode($metadata),
            ]);

            return ['success' => true, 'updated' => true];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API error during finalizePaymentWithoutWebhook: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Stripe API error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log('Finalize payment error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process Stripe webhook
     *
     * @param string $payload Raw webhook payload
     * @param string $signature Stripe signature header
     * @return array ['success' => bool, 'errors' => array]
     */
    public function processWebhook(string $payload, string $signature): array
    {
        try {
            // Verify webhook signature
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $this->stripeWebhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            error_log('Invalid webhook payload: ' . $e->getMessage());
            return [
                'success' => false,
                'errors'  => ['webhook' => 'Invalid payload'],
            ];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log('Invalid webhook signature: ' . $e->getMessage());
            return [
                'success' => false,
                'errors'  => ['webhook' => 'Invalid signature'],
            ];
        }

        // Check for duplicate event (idempotency)
        if ($this->repository->webhookEventExists($event->id)) {
            // Already processed, return success
            return [
                'success' => true,
                'errors'  => [],
            ];
        }

        // Insert webhook event record
        $this->repository->createWebhookEvent([
            'stripe_event_id' => $event->id,
            'event_type'      => $event->type,
            'payload'         => $payload,
            'processed'       => false,
        ]);

        // Handle different event types
        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object);
                    break;

                case 'transfer.created':
                    $this->handleTransferCreated($event->data->object);
                    break;

                default:
                    // Log unhandled event type
                    error_log('Unhandled webhook event type: ' . $event->type);
            }

            // Mark webhook as processed
            $this->repository->markWebhookProcessed($event->id);

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            error_log('Webhook processing error: ' . $e->getMessage());

            // Update webhook event with error
            $this->repository->updateWebhookError($event->id, $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['webhook' => 'Processing failed'],
            ];
        }
    }

    /**
     * Handle checkout.session.completed event
     *
     * @param object $session Stripe session object
     * @return void
     */
    private function handleCheckoutSessionCompleted($session): void
    {
        $rawOrderId = $session->metadata->order_id ?? null;
        $orderId    = (is_numeric($rawOrderId)) ? (int) $rawOrderId : null;

        // Update payment record first
        $this->repository->updateByCheckoutSession($session->id, [
            'stripe_payment_intent_id' => $session->payment_intent,
            'status'                   => 'succeeded',
            'metadata'                 => json_encode([
                'session_id'     => $session->id,
                'payment_intent' => $session->payment_intent,
                'completed_at'   => date('Y-m-d H:i:s'),
            ]),
        ]);

        // If we have a real order id, update order status and notify
        if ($orderId !== null) {
            // Update order status to pending (payment confirmed, awaiting student acceptance)
            $this->repository->updateOrderStatus($orderId, 'pending');

            // Send notification to student about new order
            try {
                $order   = $this->orderRepository->findById($orderId);
                $student = $this->userRepository->findById($order['student_id']);
                $client  = $this->userRepository->findById($order['client_id']);
                $service = $this->serviceRepository->findById($order['service_id']);

                if ($order && $student && $client && $service) {
                    $this->notificationService->notifyOrderPlaced($order, $student, $client, $service);
                }
            } catch (Exception $e) {
                // Log error but don't fail the payment processing
                error_log('Failed to send order placed notification: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle charge.refunded event
     *
     * @param object $charge Stripe charge object
     * @return void
     */
    private function handleChargeRefunded($charge): void
    {
        $paymentIntentId = $charge->payment_intent;

        if (! $paymentIntentId) {
            throw new Exception('Payment intent ID not found in charge');
        }

                                                        // Calculate refund amount
        $refundAmount = $charge->amount_refunded / 100; // Convert from cents

        // Update payment record
        $this->repository->updateByPaymentIntent($paymentIntentId, [
            'status'        => 'refunded',
            'refund_amount' => $refundAmount,
        ]);
    }

    /**
     * Handle transfer.created event
     *
     * @param object $transfer Stripe transfer object
     * @return void
     */
    private function handleTransferCreated($transfer): void
    {
        $orderId = $transfer->metadata->order_id ?? null;

        if (! $orderId) {
            // Transfer might not have order metadata, skip
            return;
        }

        // Update payment record with transfer ID
        $this->repository->updateByOrderId((int) $orderId, [
            'stripe_transfer_id' => $transfer->id,
        ]);
    }

    /**
     * Release payment to student (Balance System)
     *
     * Adds funds to student's available balance instead of direct Stripe transfer.
     * Actual Stripe transfer happens when student requests withdrawal.
     *
     * @param array $order Order data
     * @return array ['success' => bool, 'errors' => array]
     */
    public function releasePayment(array $order): array
    {
        try {
            // Get payment record
            $payment = $this->repository->findByOrderId($order['id']);

            if (! $payment) {
                return [
                    'success' => false,
                    'errors'  => ['payment' => 'Payment not found'],
                ];
            }

            if ($payment['status'] !== 'succeeded') {
                return [
                    'success' => false,
                    'errors'  => ['payment' => 'Payment not in succeeded status'],
                ];
            }

            // Check idempotency - if already released, return success
            $metadata = json_decode($payment['metadata'], true) ?? [];
            if (isset($metadata['released_at'])) {
                return [
                    'success' => true,
                    'errors'  => [],
                ];
            }

            // Calculate commission and student amount
            $orderAmount      = $payment['amount'];
            $commissionRate   = $order['commission_rate'];
            $commissionAmount = $orderAmount * ($commissionRate / 100);
            $studentAmount    = $orderAmount - $commissionAmount;

            // Begin transaction
            $this->db->beginTransaction();

            try {
                // Add to student's available balance
                $this->withdrawalService->addToBalance($order['student_id'], $studentAmount, 'available');

                // Update payment record with release timestamp
                $metadata['released_at']       = date('Y-m-d H:i:s');
                $metadata['commission_amount'] = $commissionAmount;
                $metadata['student_amount']    = $studentAmount;

                $this->repository->update($payment['id'], [
                    'metadata' => json_encode($metadata),
                ]);

                // Insert audit log entry
                $this->insertAuditLog([
                    'user_id'       => $order['student_id'],
                    'action'        => 'payment.released',
                    'resource_type' => 'payment',
                    'resource_id'   => $payment['id'],
                    'old_values'    => json_encode(['status' => 'succeeded']),
                    'new_values'    => json_encode([
                        'status'            => 'succeeded',
                        'released_at'       => $metadata['released_at'],
                        'student_amount'    => $studentAmount,
                        'commission_amount' => $commissionAmount,
                    ]),
                    'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ]);

                $this->db->commit();

                return [
                    'success' => true,
                    'errors'  => [],
                ];
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            error_log('Payment release error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['payment' => 'An error occurred. Please contact support.'],
            ];
        }
    }

    /**
     * Refund payment to client
     *
     * @param array $order Order data
     * @param float|null $amount Amount to refund (null for full refund)
     * @return array ['success' => bool, 'errors' => array]
     */
    public function refundPayment(array $order, ?float $amount = null): array
    {
        // Get payment record
        $payment = $this->repository->findByOrderId($order['id']);

        if (! $payment) {
            // Log payment not found error with context
            error_log(json_encode([
                'error'     => 'Payment not found',
                'order_id'  => $order['id'],
                'timestamp' => date('Y-m-d H:i:s'),
            ]));

            return [
                'success' => false,
                'errors'  => ['payment' => 'Payment not found'],
            ];
        }

        if ($payment['status'] !== 'succeeded' && $payment['status'] !== 'partially_refunded') {
            // Log invalid status error with context
            error_log(json_encode([
                'error'      => 'Payment cannot be refunded in current status',
                'order_id'   => $order['id'],
                'payment_id' => $payment['id'],
                'status'     => $payment['status'],
                'timestamp'  => date('Y-m-d H:i:s'),
            ]));

            return [
                'success' => false,
                'errors'  => ['payment' => 'Payment cannot be refunded in current status'],
            ];
        }

        // Determine refund amount
        $refundAmount = $amount ?? $payment['amount'];

        // Check idempotency - use order_id + operation type as key
        $metadata       = json_decode($payment['metadata'], true) ?? [];
        $idempotencyKey = 'refund_' . $order['id'] . '_' . number_format($refundAmount, 2);

        if (isset($metadata['refund_operations'][$idempotencyKey])) {
            // Already processed this refund
            return [
                'success' => true,
                'errors'  => [],
            ];
        }

        // Log refund initiation for audit trail
        error_log(json_encode([
            'event'                    => 'refund_initiated',
            'order_id'                 => $order['id'],
            'payment_id'               => $payment['id'],
            'stripe_payment_intent_id' => $payment['stripe_payment_intent_id'],
            'amount'                   => $refundAmount,
            'user_id'                  => $order['client_id'],
            'timestamp'                => date('Y-m-d H:i:s'),
        ]));

        // Retry logic for Stripe API calls
        $maxRetries = 2;
        $retryDelay = 5; // seconds
        $refund     = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                // Create refund with idempotency key
                $refund = \Stripe\Refund::create([
                    'payment_intent' => $payment['stripe_payment_intent_id'],
                    'amount'         => (int) ($refundAmount * 100), // Convert to cents
                ], [
                    'idempotency_key' => $idempotencyKey,
                ]);

                // Success - break retry loop
                break;
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Log comprehensive error details
                error_log(json_encode([
                    'error'                    => 'Stripe refund failed',
                    'attempt'                  => $attempt + 1,
                    'max_attempts'             => $maxRetries + 1,
                    'order_id'                 => $order['id'],
                    'payment_id'               => $payment['id'],
                    'stripe_payment_intent_id' => $payment['stripe_payment_intent_id'],
                    'amount'                   => $refundAmount,
                    'stripe_error_type'        => get_class($e),
                    'stripe_error_message'     => $e->getMessage(),
                    'stripe_error_code'        => $e->getStripeCode(),
                    'timestamp'                => date('Y-m-d H:i:s'),
                ]));

                if ($attempt < $maxRetries) {
                    // Log retry attempt
                    error_log(json_encode([
                        'event'      => 'refund_retry',
                        'order_id'   => $order['id'],
                        'payment_id' => $payment['id'],
                        'attempt'    => $attempt + 2,
                        'delay'      => $retryDelay,
                        'timestamp'  => date('Y-m-d H:i:s'),
                    ]));
                    sleep($retryDelay);
                } else {
                    // All retries exhausted, log final failure
                    error_log(json_encode([
                        'error'                    => 'Refund failed after all retries',
                        'order_id'                 => $order['id'],
                        'payment_id'               => $payment['id'],
                        'stripe_payment_intent_id' => $payment['stripe_payment_intent_id'],
                        'amount'                   => $refundAmount,
                        'total_attempts'           => $maxRetries + 1,
                        'stripe_error_message'     => $e->getMessage(),
                        'timestamp'                => date('Y-m-d H:i:s'),
                    ]));

                    return [
                        'success' => false,
                        'errors'  => ['payment' => 'Refund failed. Please contact support.'],
                    ];
                }
            }
        }

        // Verify refund was created successfully before updating database
        if (! $refund || ! isset($refund->id)) {
            error_log(json_encode([
                'error'      => 'Refund object invalid after Stripe call',
                'order_id'   => $order['id'],
                'payment_id' => $payment['id'],
                'timestamp'  => date('Y-m-d H:i:s'),
            ]));

            return [
                'success' => false,
                'errors'  => ['payment' => 'Refund failed. Please contact support.'],
            ];
        }

        // Transaction-safe section: only manage transaction if we own it
        $ownsTx = ! $this->db->inTransaction();
        if ($ownsTx) {
            $this->db->beginTransaction();
        }

        try {
            // Update payment record
            $newStatus         = ($refundAmount >= $payment['amount']) ? 'refunded' : 'partially_refunded';
            $totalRefundAmount = $payment['refund_amount'] + $refundAmount;

            // Store idempotency key in metadata
            if (! isset($metadata['refund_operations'])) {
                $metadata['refund_operations'] = [];
            }
            $metadata['refund_operations'][$idempotencyKey] = [
                'amount'      => $refundAmount,
                'refund_id'   => $refund->id,
                'refunded_at' => date('Y-m-d H:i:s'),
            ];

            $this->repository->update($payment['id'], [
                'status'        => $newStatus,
                'refund_amount' => $totalRefundAmount,
                'metadata'      => json_encode($metadata),
            ]);

            // Insert audit log entry
            $this->insertAuditLog([
                'user_id'       => $order['client_id'],
                'action'        => 'payment.refunded',
                'resource_type' => 'payment',
                'resource_id'   => $payment['id'],
                'old_values'    => json_encode([
                    'status'        => $payment['status'],
                    'refund_amount' => $payment['refund_amount'],
                ]),
                'new_values'    => json_encode([
                    'status'        => $newStatus,
                    'refund_amount' => $totalRefundAmount,
                    'refund_id'     => $refund->id,
                ]),
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            // Commit transaction only if we started it
            if ($ownsTx) {
                $this->db->commit();
            }

            // Log successful refund completion
            error_log(json_encode([
                'event'                    => 'refund_completed',
                'order_id'                 => $order['id'],
                'payment_id'               => $payment['id'],
                'stripe_payment_intent_id' => $payment['stripe_payment_intent_id'],
                'amount'                   => $refundAmount,
                'refund_id'                => $refund->id,
                'user_id'                  => $order['client_id'],
                'timestamp'                => date('Y-m-d H:i:s'),
            ]));

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            // Rollback transaction only if we started it
            if ($ownsTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            // Log transaction rollback
            error_log(json_encode([
                'error'                    => 'Database transaction failed, rolled back',
                'order_id'                 => $order['id'],
                'payment_id'               => $payment['id'],
                'stripe_payment_intent_id' => $payment['stripe_payment_intent_id'],
                'amount'                   => $refundAmount,
                'refund_id'                => $refund->id,
                'exception_message'        => $e->getMessage(),
                'timestamp'                => date('Y-m-d H:i:s'),
            ]));

            return [
                'success' => false,
                'errors'  => ['payment' => 'An error occurred. Please contact support.'],
            ];
        }
    }

    /**
     * Insert audit log entry
     *
     * @param array $data Audit log data
     * @return void
     */
    private function insertAuditLog(array $data): void
    {
        $sql = "INSERT INTO audit_logs (
            user_id, action, resource_type, resource_id,
            old_values, new_values, ip_address, user_agent, created_at
        ) VALUES (
            :user_id, :action, :resource_type, :resource_id,
            :old_values, :new_values, :ip_address, :user_agent, NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id'       => $data['user_id'] ?? null,
            'action'        => $data['action'],
            'resource_type' => $data['resource_type'],
            'resource_id'   => $data['resource_id'] ?? null,
            'old_values'    => $data['old_values'] ?? null,
            'new_values'    => $data['new_values'] ?? null,
            'ip_address'    => $data['ip_address'] ?? null,
            'user_agent'    => $data['user_agent'] ?? null,
        ]);
    }

    /**
     * Helper: fetch payment by id directly (since repository lacks dedicated method).
     */
    private function repositoryPaymentById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
