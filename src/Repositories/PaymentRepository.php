<?php

/**
 * Payment Repository
 *
 * Handles database operations for payments and webhook events
 */
class PaymentRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new payment record
     *
     * @param array $data Payment data
     * @return int The ID of the created payment
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO payments (
            order_id, stripe_payment_intent_id, stripe_checkout_session_id,
            stripe_transfer_id, amount, commission_amount, student_amount,
            status, refund_amount, metadata, created_at, updated_at
        ) VALUES (
            :order_id, :stripe_payment_intent_id, :stripe_checkout_session_id,
            :stripe_transfer_id, :amount, :commission_amount, :student_amount,
            :status, :refund_amount, :metadata, NOW(), NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_id'                   => $data['order_id'] ?? null,
            'stripe_payment_intent_id'   => $data['stripe_payment_intent_id'] ?? null, // allow NULL until webhook
            'stripe_checkout_session_id' => $data['stripe_checkout_session_id'] ?? null,
            'stripe_transfer_id'         => $data['stripe_transfer_id'] ?? null,
            'amount'                     => $data['amount'],
            'commission_amount'          => $data['commission_amount'],
            'student_amount'             => $data['student_amount'],
            'status'                     => $data['status'],
            'refund_amount'              => $data['refund_amount'] ?? 0.00,
            'metadata'                   => $data['metadata'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a payment record
     *
     * @param int $id Payment ID
     * @param array $data Data to update
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        // allow linking payment to order after order is created
        if (array_key_exists('order_id', $data)) {
            $fields[]           = 'order_id = :order_id';
            $params['order_id'] = $data['order_id'];
        }

        if (isset($data['stripe_payment_intent_id'])) {
            $fields[]                           = 'stripe_payment_intent_id = :stripe_payment_intent_id';
            $params['stripe_payment_intent_id'] = $data['stripe_payment_intent_id'];
        }

        if (isset($data['stripe_transfer_id'])) {
            $fields[]                     = 'stripe_transfer_id = :stripe_transfer_id';
            $params['stripe_transfer_id'] = $data['stripe_transfer_id'];
        }

        if (isset($data['status'])) {
            $fields[]         = 'status = :status';
            $params['status'] = $data['status'];
        }

        if (isset($data['refund_amount'])) {
            $fields[]                = 'refund_amount = :refund_amount';
            $params['refund_amount'] = $data['refund_amount'];
        }

        if (isset($data['commission_amount'])) {
            $fields[]                    = 'commission_amount = :commission_amount';
            $params['commission_amount'] = $data['commission_amount'];
        }

        if (isset($data['student_amount'])) {
            $fields[]                 = 'student_amount = :student_amount';
            $params['student_amount'] = $data['student_amount'];
        }

        if (isset($data['metadata'])) {
            $fields[]           = 'metadata = :metadata';
            $params['metadata'] = $data['metadata'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';

        $sql  = "UPDATE payments SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Find payment by order ID
     *
     * @param int $orderId
     * @return array|null
     */
    public function findByOrderId(int $orderId): ?array
    {
        $sql  = "SELECT * FROM payments WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $payment = $stmt->fetch();

        return $payment ?: null;
    }

    /**
     * Find payment by checkout session ID
     *
     * @param string $sessionId
     * @return array|null
     */
    public function findByCheckoutSession(string $sessionId): ?array
    {
        $sql  = "SELECT * FROM payments WHERE stripe_checkout_session_id = :session_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['session_id' => $sessionId]);

        $payment = $stmt->fetch();

        return $payment ?: null;
    }

    /**
     * Find payment by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql  = "SELECT * FROM payments WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $payment = $stmt->fetch();

        return $payment ?: null;
    }

    /**
     * Update payment by checkout session ID
     *
     * @param string $sessionId
     * @param array $data
     * @return bool
     */
    public function updateByCheckoutSession(string $sessionId, array $data): bool
    {
        $fields = [];
        $params = ['session_id' => $sessionId];

        if (isset($data['stripe_payment_intent_id'])) {
            $fields[]                           = 'stripe_payment_intent_id = :stripe_payment_intent_id';
            $params['stripe_payment_intent_id'] = $data['stripe_payment_intent_id'];
        }

        if (isset($data['status'])) {
            $fields[]         = 'status = :status';
            $params['status'] = $data['status'];
        }

        if (isset($data['metadata'])) {
            $fields[]           = 'metadata = :metadata';
            $params['metadata'] = $data['metadata'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';

        $sql = "UPDATE payments SET " . implode(', ', $fields) .
            " WHERE stripe_checkout_session_id = :session_id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Update payment by payment intent ID
     *
     * @param string $paymentIntentId
     * @param array $data
     * @return bool
     */
    public function updateByPaymentIntent(string $paymentIntentId, array $data): bool
    {
        $fields = [];
        $params = ['payment_intent_id' => $paymentIntentId];

        if (isset($data['status'])) {
            $fields[]         = 'status = :status';
            $params['status'] = $data['status'];
        }

        if (isset($data['refund_amount'])) {
            $fields[]                = 'refund_amount = :refund_amount';
            $params['refund_amount'] = $data['refund_amount'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';

        $sql = "UPDATE payments SET " . implode(', ', $fields) .
            " WHERE stripe_payment_intent_id = :payment_intent_id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Update payment by order ID
     *
     * @param int $orderId
     * @param array $data
     * @return bool
     */
    public function updateByOrderId(int $orderId, array $data): bool
    {
        $fields = [];
        $params = ['order_id' => $orderId];

        if (isset($data['stripe_transfer_id'])) {
            $fields[]                     = 'stripe_transfer_id = :stripe_transfer_id';
            $params['stripe_transfer_id'] = $data['stripe_transfer_id'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';

        $sql  = "UPDATE payments SET " . implode(', ', $fields) . " WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Update order status
     *
     * @param int $orderId
     * @param string $status
     * @return bool
     */
    public function updateOrderStatus(int $orderId, string $status): bool
    {
        $sql  = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id'     => $orderId,
            'status' => $status,
        ]);
    }

    /**
     * Get student's Stripe Connect account ID
     *
     * @param int $studentId
     * @return string|null
     */
    public function getStudentStripeAccount(int $studentId): ?string
    {
        $sql  = "SELECT stripe_connect_account_id FROM student_profiles WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $studentId]);

        $result = $stmt->fetch();

        return $result['stripe_connect_account_id'] ?? null;
    }

    /**
     * Check if webhook event exists (for idempotency)
     *
     * @param string $stripeEventId
     * @return bool
     */
    public function webhookEventExists(string $stripeEventId): bool
    {
        $sql  = "SELECT COUNT(*) as count FROM webhook_events WHERE stripe_event_id = :event_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['event_id' => $stripeEventId]);

        $result = $stmt->fetch();

        return $result['count'] > 0;
    }

    /**
     * Create webhook event record
     *
     * @param array $data
     * @return int
     */
    public function createWebhookEvent(array $data): int
    {
        $sql = "INSERT INTO webhook_events (
            stripe_event_id, event_type, payload, processed, created_at
        ) VALUES (
            :stripe_event_id, :event_type, :payload, :processed, NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'stripe_event_id' => $data['stripe_event_id'],
            'event_type'      => $data['event_type'],
            'payload'         => $data['payload'],
            'processed'       => $data['processed'] ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Mark webhook event as processed
     *
     * @param string $stripeEventId
     * @return bool
     */
    public function markWebhookProcessed(string $stripeEventId): bool
    {
        $sql = "UPDATE webhook_events
                SET processed = 1, processed_at = NOW()
                WHERE stripe_event_id = :event_id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['event_id' => $stripeEventId]);
    }

    /**
     * Update webhook event with error
     *
     * @param string $stripeEventId
     * @param string $error
     * @return bool
     */
    public function updateWebhookError(string $stripeEventId, string $error): bool
    {
        $sql = "UPDATE webhook_events
                SET error = :error
                WHERE stripe_event_id = :event_id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'event_id' => $stripeEventId,
            'error'    => $error,
        ]);
    }
}
