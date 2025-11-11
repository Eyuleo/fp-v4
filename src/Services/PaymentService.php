<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Repositories/PaymentRepository.php';

/**
 * Payment Service
 *
 * Handles payment processing with Stripe
 */
class PaymentService
{
    private PaymentRepository $repository;
    private string $stripeSecretKey;
    private string $stripeWebhookSecret;

    public function __construct(PaymentRepository $repository)
    {
        $this->repository          = $repository;
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
     * @return array ['success' => bool, 'session_url' => string|null, 'session_id' => string|null, 'errors' => array]
     */
    public function createCheckoutSession(array $order, string $successUrl, string $cancelUrl): array
    {
        try {
            // Create checkout session
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => 'usd',
                        'product_data' => [
                            'name'        => $order['service_title'],
                            'description' => 'Order #' . $order['id'],
                        ],
                        'unit_amount'  => (int) ($order['price'] * 100), // Convert to cents
                    ],
                    'quantity'   => 1,
                ]],
                'mode'                 => 'payment',
                'success_url'          => $successUrl,
                'cancel_url'           => $cancelUrl,
                'metadata'             => [
                    'order_id'   => $order['id'],
                    'client_id'  => $order['client_id'],
                    'student_id' => $order['student_id'],
                ],
            ]);

            // Create payment record with pending status
            $paymentId = $this->repository->create([
                'order_id'                   => $order['id'],
                'stripe_payment_intent_id'   => '', // Will be updated by webhook
                'stripe_checkout_session_id' => $session->id,
                'amount'                     => $order['price'],
                'commission_amount'          => $order['price'] * ($order['commission_rate'] / 100),
                'student_amount'             => $order['price'] - ($order['price'] * ($order['commission_rate'] / 100)),
                'status'                     => 'pending',
                'metadata'                   => json_encode([
                    'session_id' => $session->id,
                    'created_at' => date('Y-m-d H:i:s'),
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
        $orderId = $session->metadata->order_id ?? null;

        if (! $orderId) {
            throw new Exception('Order ID not found in session metadata');
        }

        // Update payment record
        $this->repository->updateByCheckoutSession($session->id, [
            'stripe_payment_intent_id' => $session->payment_intent,
            'status'                   => 'succeeded',
            'metadata'                 => json_encode([
                'session_id'     => $session->id,
                'payment_intent' => $session->payment_intent,
                'completed_at'   => date('Y-m-d H:i:s'),
            ]),
        ]);

        // Update order status to pending (payment confirmed, awaiting student acceptance)
        $this->repository->updateOrderStatus($orderId, 'pending');
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
        $this->repository->updateByOrderId($orderId, [
            'stripe_transfer_id' => $transfer->id,
        ]);
    }

    /**
     * Release payment to student (called after order completion)
     *
     * @param array $order Order data
     * @return array ['success' => bool, 'errors' => array]
     */
    public function releasePayment(array $order): array
    {
        try {
            // Get payment record
            $payment = $this->repository->findByOrderId($order['id']);

            if (! $payment || $payment['status'] !== 'succeeded') {
                return [
                    'success' => false,
                    'errors'  => ['payment' => 'Payment not found or not in succeeded status'],
                ];
            }

            // Get student's Stripe Connect account ID
            $studentConnectAccountId = $this->repository->getStudentStripeAccount($order['student_id']);

            if (! $studentConnectAccountId) {
                return [
                    'success' => false,
                    'errors'  => ['payment' => 'Student Stripe account not found'],
                ];
            }

            // Create transfer to student
            $transfer = \Stripe\Transfer::create([
                'amount'      => (int) ($payment['student_amount'] * 100), // Convert to cents
                'currency'    => 'usd',
                'destination' => $studentConnectAccountId,
                'metadata'    => [
                    'order_id'   => $order['id'],
                    'student_id' => $order['student_id'],
                ],
            ]);

            // Update payment record
            $this->repository->update($payment['id'], [
                'stripe_transfer_id' => $transfer->id,
            ]);

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe transfer error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['payment' => 'Transfer failed. Please contact support.'],
            ];
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
        try {
            // Get payment record
            $payment = $this->repository->findByOrderId($order['id']);

            if (! $payment || $payment['status'] !== 'succeeded') {
                return [
                    'success' => false,
                    'errors'  => ['payment' => 'Payment not found or not in succeeded status'],
                ];
            }

            // Determine refund amount
            $refundAmount = $amount ?? $payment['amount'];

            // Create refund
            $refund = \Stripe\Refund::create([
                'payment_intent' => $payment['stripe_payment_intent_id'],
                'amount'         => (int) ($refundAmount * 100), // Convert to cents
            ]);

            // Update payment record
            $newStatus = ($refundAmount >= $payment['amount']) ? 'refunded' : 'partially_refunded';

            $this->repository->update($payment['id'], [
                'status'        => $newStatus,
                'refund_amount' => $refundAmount,
            ]);

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe refund error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['payment' => 'Refund failed. Please contact support.'],
            ];
        } catch (Exception $e) {
            error_log('Payment refund error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['payment' => 'An error occurred. Please contact support.'],
            ];
        }
    }
}
