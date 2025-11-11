<?php

/**
 * Withdrawal Service
 *
 * Handles student balance withdrawals to Stripe Connect accounts
 */
class WithdrawalService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Request withdrawal
     */
    public function requestWithdrawal(int $studentId, float $amount): array
    {
        // Get student profile
        $stmt = $this->db->prepare('
            SELECT available_balance, stripe_connect_account_id, stripe_onboarding_complete
            FROM student_profiles
            WHERE user_id = :user_id
        ');
        $stmt->execute(['user_id' => $studentId]);
        $profile = $stmt->fetch();

        if (! $profile) {
            throw new Exception('Student profile not found');
        }

        // Validate Stripe Connect
        if (! $profile['stripe_onboarding_complete']) {
            throw new Exception('Please complete Stripe Connect onboarding before withdrawing funds');
        }

        // Validate amount
        if ($amount <= 0) {
            throw new Exception('Withdrawal amount must be greater than zero');
        }

        if ($amount > $profile['available_balance']) {
            throw new Exception('Insufficient balance. Available: $' . number_format($profile['available_balance'], 2));
        }

        // Minimum withdrawal amount
        if ($amount < 10) {
            throw new Exception('Minimum withdrawal amount is $10.00');
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Deduct from available balance
            $stmt = $this->db->prepare('
                UPDATE student_profiles
                SET available_balance = available_balance - :amount,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ');
            $stmt->execute([
                'amount'  => $amount,
                'user_id' => $studentId,
            ]);

            // Create withdrawal record
            $stmt = $this->db->prepare('
                INSERT INTO withdrawals (
                    student_id, amount, status, stripe_connect_account_id, requested_at
                )
                VALUES (
                    :student_id, :amount, :status, :stripe_account_id, NOW()
                )
            ');
            $stmt->execute([
                'student_id'        => $studentId,
                'amount'            => $amount,
                'status'            => 'pending',
                'stripe_account_id' => $profile['stripe_connect_account_id'],
            ]);

            $withdrawalId = (int) $this->db->lastInsertId();

            $this->db->commit();

            // Process withdrawal asynchronously (in real app, this would be a queue job)
            $this->processWithdrawal($withdrawalId);

            return [
                'id'     => $withdrawalId,
                'amount' => $amount,
                'status' => 'pending',
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Process withdrawal via Stripe Transfer
     */
    public function processWithdrawal(int $withdrawalId): bool
    {
        // Get withdrawal record
        $stmt = $this->db->prepare('
            SELECT w.*, sp.stripe_connect_account_id
            FROM withdrawals w
            INNER JOIN student_profiles sp ON w.student_id = sp.user_id
            WHERE w.id = :id AND w.status = :status
        ');
        $stmt->execute([
            'id'     => $withdrawalId,
            'status' => 'pending',
        ]);
        $withdrawal = $stmt->fetch();

        if (! $withdrawal) {
            return false;
        }

        try {
            // Update status to processing
            $stmt = $this->db->prepare('
                UPDATE withdrawals
                SET status = :status, updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                'status' => 'processing',
                'id'     => $withdrawalId,
            ]);

            // Initialize Stripe
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));

            // Create transfer to Connect account
            // Amount in cents
            $amountCents = (int) ($withdrawal['amount'] * 100);

            $transfer = \Stripe\Transfer::create([
                'amount'      => $amountCents,
                'currency'    => 'usd',
                'destination' => $withdrawal['stripe_connect_account_id'],
                'description' => 'Withdrawal for student ID: ' . $withdrawal['student_id'],
                'metadata'    => [
                    'withdrawal_id' => $withdrawalId,
                    'student_id'    => $withdrawal['student_id'],
                ],
            ]);

            // Update withdrawal as completed
            $stmt = $this->db->prepare('
                UPDATE withdrawals
                SET status = :status,
                    stripe_transfer_id = :transfer_id,
                    processed_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                'status'      => 'completed',
                'transfer_id' => $transfer->id,
                'id'          => $withdrawalId,
            ]);

            // Update total withdrawn
            $stmt = $this->db->prepare('
                UPDATE student_profiles
                SET total_withdrawn = total_withdrawn + :amount,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ');
            $stmt->execute([
                'amount'  => $withdrawal['amount'],
                'user_id' => $withdrawal['student_id'],
            ]);

            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Handle Stripe error
            $stmt = $this->db->prepare('
                UPDATE withdrawals
                SET status = :status,
                    failure_reason = :reason,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                'status' => 'failed',
                'reason' => $e->getMessage(),
                'id'     => $withdrawalId,
            ]);

            // Refund balance
            $stmt = $this->db->prepare('
                UPDATE student_profiles
                SET available_balance = available_balance + :amount,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ');
            $stmt->execute([
                'amount'  => $withdrawal['amount'],
                'user_id' => $withdrawal['student_id'],
            ]);

            throw new Exception('Withdrawal failed: ' . $e->getMessage());
        }
    }

    /**
     * Get withdrawal history for student
     */
    public function getWithdrawalHistory(int $studentId, int $limit = 20): array
    {
        $stmt = $this->db->prepare('
            SELECT *
            FROM withdrawals
            WHERE student_id = :student_id
            ORDER BY requested_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue('student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get student balance
     */
    public function getBalance(int $studentId): array
    {
        $stmt = $this->db->prepare('
            SELECT available_balance, pending_balance, total_withdrawn
            FROM student_profiles
            WHERE user_id = :user_id
        ');
        $stmt->execute(['user_id' => $studentId]);

        $balance = $stmt->fetch();

        if (! $balance) {
            return [
                'available_balance' => 0.00,
                'pending_balance'   => 0.00,
                'total_withdrawn'   => 0.00,
            ];
        }

        return $balance;
    }

    /**
     * Add to student balance (called when order is completed)
     */
    public function addToBalance(int $studentId, float $amount, string $type = 'available'): bool
    {
        $field = $type === 'pending' ? 'pending_balance' : 'available_balance';

        $stmt = $this->db->prepare("
            UPDATE student_profiles
            SET {$field} = {$field} + :amount,
                updated_at = NOW()
            WHERE user_id = :user_id
        ");

        return $stmt->execute([
            'amount'  => $amount,
            'user_id' => $studentId,
        ]);
    }

    /**
     * Move balance from pending to available
     */
    public function movePendingToAvailable(int $studentId, float $amount): bool
    {
        $stmt = $this->db->prepare('
            UPDATE student_profiles
            SET pending_balance = pending_balance - :amount,
                available_balance = available_balance + :amount,
                updated_at = NOW()
            WHERE user_id = :user_id
        ');

        return $stmt->execute([
            'amount'  => $amount,
            'user_id' => $studentId,
        ]);
    }
}
