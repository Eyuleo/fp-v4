<?php

require_once __DIR__ . '/../Repositories/DisputeRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/NotificationService.php';

/**
 * Dispute Service
 *
 * Business logic for dispute management
 */
class DisputeService
{
    private DisputeRepository $disputeRepository;
    private OrderRepository $orderRepository;
    private NotificationService $notificationService;
    private PDO $db;

    public function __construct(
        DisputeRepository $disputeRepository,
        OrderRepository $orderRepository,
        NotificationService $notificationService,
        PDO $db
    ) {
        $this->disputeRepository   = $disputeRepository;
        $this->orderRepository     = $orderRepository;
        $this->notificationService = $notificationService;
        $this->db                  = $db;
    }

    /**
     * Create a new dispute
     *
     * @param int $orderId Order ID
     * @param int $userId User ID (client or student)
     * @param string $reason Dispute reason
     * @return array ['success' => bool, 'dispute_id' => int|null, 'errors' => array]
     */
    public function createDispute(int $orderId, int $userId, string $reason): array
    {
        // Get order
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            return [
                'success'    => false,
                'dispute_id' => null,
                'errors'     => ['order' => 'Order not found'],
            ];
        }

        // Verify user is authorized (client or student of the order)
        if ($order['client_id'] != $userId && $order['student_id'] != $userId) {
            return [
                'success'    => false,
                'dispute_id' => null,
                'errors'     => ['authorization' => 'You are not authorized to open a dispute for this order'],
            ];
        }

        // Check order status allows dispute (in_progress, delivered, revision_requested)
        $allowedStatuses = ['in_progress', 'delivered', 'revision_requested'];
        if (! in_array($order['status'], $allowedStatuses)) {
            return [
                'success'    => false,
                'dispute_id' => null,
                'errors'     => ['status' => 'Disputes can only be opened for orders in progress, delivered, or revision requested status'],
            ];
        }

        // Check if dispute already exists for this order
        $existingDispute = $this->disputeRepository->findByOrderId($orderId);
        if ($existingDispute) {
            return [
                'success'    => false,
                'dispute_id' => null,
                'errors'     => ['dispute' => 'A dispute already exists for this order'],
            ];
        }

        // Validate reason
        $reason = trim($reason);
        if (empty($reason) || strlen($reason) < 10) {
            return [
                'success'    => false,
                'dispute_id' => null,
                'errors'     => ['reason' => 'Please provide a detailed reason for the dispute (minimum 10 characters)'],
            ];
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Create dispute record
            $disputeId = $this->disputeRepository->create([
                'order_id'  => $orderId,
                'opened_by' => $userId,
                'reason'    => $reason,
                'status'    => 'open',
            ]);

            // Get admin users, client, student, and service for notifications
            require_once __DIR__ . '/../Repositories/UserRepository.php';
            require_once __DIR__ . '/../Repositories/ServiceRepository.php';

            $userRepository    = new UserRepository($this->db);
            $serviceRepository = new ServiceRepository($this->db);

            $client  = $userRepository->findById($order['client_id']);
            $student = $userRepository->findById($order['student_id']);
            $service = $serviceRepository->findById($order['service_id']);

            // Get the created dispute
            $dispute = $this->disputeRepository->findById($disputeId);

            // Get admin users
            $adminSql  = "SELECT id, email, name FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1";
            $adminStmt = $this->db->prepare($adminSql);
            $adminStmt->execute();
            $admin = $adminStmt->fetch();

            // Send notification to admin and parties
            if ($admin && $client && $student && $service && $dispute) {
                try {
                    $this->notificationService->notifyDisputeOpened($dispute, $order, $client, $student, $service, $admin);
                } catch (Exception $e) {
                    error_log('Failed to send dispute opened notification: ' . $e->getMessage());
                }
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success'    => true,
                'dispute_id' => $disputeId,
                'errors'     => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollBack();
            error_log('Dispute creation error: ' . $e->getMessage());

            return [
                'success'    => false,
                'dispute_id' => null,
                'errors'     => ['database' => 'Failed to create dispute. Please try again.'],
            ];
        }
    }

    /**
     * Get dispute by ID
     *
     * @param int $disputeId Dispute ID
     * @return array|null
     */
    public function getDisputeById(int $disputeId): ?array
    {
        return $this->disputeRepository->findById($disputeId);
    }

    /**
     * Get all disputes with filters
     *
     * @param string|null $status Filter by status
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     */
    public function getAllDisputes(?string $status = null, int $page = 1, int $perPage = 20): array
    {
        return $this->disputeRepository->getAll($status, $page, $perPage);
    }

    /**
     * Resolve a dispute
     *
     * @param int $disputeId Dispute ID
     * @param int $adminId Admin user ID
     * @param string $resolution Resolution type (release_to_student, refund_to_client, partial_refund)
     * @param string $resolutionNotes Notes explaining the decision
     * @param float|null $partialAmount Partial refund amount (for partial_refund only)
     * @return array ['success' => bool, 'errors' => array]
     */
    public function resolveDispute(
        int $disputeId,
        int $adminId,
        string $resolution,
        string $resolutionNotes,
        ?float $partialAmount = null
    ): array {
        // Get dispute
        $dispute = $this->disputeRepository->findById($disputeId);

        if (! $dispute) {
            return [
                'success' => false,
                'errors'  => ['dispute' => 'Dispute not found'],
            ];
        }

        // Check dispute is still open
        if ($dispute['status'] !== 'open') {
            return [
                'success' => false,
                'errors'  => ['status' => 'Dispute has already been resolved'],
            ];
        }

        // Validate resolution type
        $validResolutions = ['release_to_student', 'refund_to_client', 'partial_refund'];
        if (! in_array($resolution, $validResolutions)) {
            return [
                'success' => false,
                'errors'  => ['resolution' => 'Invalid resolution type'],
            ];
        }

        // Validate resolution notes
        $resolutionNotes = trim($resolutionNotes);
        if (empty($resolutionNotes)) {
            return [
                'success' => false,
                'errors'  => ['resolution_notes' => 'Resolution notes are required'],
            ];
        }

        // Validate partial amount if partial refund
        if ($resolution === 'partial_refund') {
            if ($partialAmount === null || $partialAmount <= 0) {
                return [
                    'success' => false,
                    'errors'  => ['partial_amount' => 'Valid refund amount is required for partial refund'],
                ];
            }

            if ($partialAmount > $dispute['order_price']) {
                return [
                    'success' => false,
                    'errors'  => ['partial_amount' => 'Refund amount cannot exceed order price'],
                ];
            }
        }

        // Get order
        $order = $this->orderRepository->findById($dispute['order_id']);

        if (! $order) {
            return [
                'success' => false,
                'errors'  => ['order' => 'Order not found'],
            ];
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Execute resolution based on type
            switch ($resolution) {
                case 'release_to_student':
                    $result = $this->releaseToStudent($order, $adminId);
                    break;

                case 'refund_to_client':
                    $result = $this->refundToClient($order, $adminId);
                    break;

                case 'partial_refund':
                    $result = $this->partialRefund($order, $adminId, $partialAmount);
                    break;
            }

            if (! $result['success']) {
                $this->db->rollBack();
                return $result;
            }

            // Update dispute status
            $this->disputeRepository->update($disputeId, [
                'status'           => 'resolved',
                'resolution'       => $resolution,
                'resolution_notes' => $resolutionNotes,
                'resolved_by'      => $adminId,
                'resolved_at'      => date('Y-m-d H:i:s'),
            ]);

            // Send notifications to both parties
            $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';

            // Notify client
            $this->notificationService->notify(
                $order['client_id'],
                $order['client_email'],
                'dispute_resolved',
                'Dispute Resolved',
                "The dispute for order #{$order['id']} has been resolved",
                'emails/dispute-resolved',
                [
                    'user_name'        => $order['client_name'] ?? $order['client_email'],
                    'order_id'         => $order['id'],
                    'service_title'    => $order['service_title'],
                    'resolution'       => $this->getResolutionLabel($resolution),
                    'resolution_notes' => $resolutionNotes,
                    'order_url'        => $appUrl . '/orders/' . $order['id'],
                ],
                $appUrl . '/orders/' . $order['id']
            );

            // Notify student
            $this->notificationService->notify(
                $order['student_id'],
                $order['student_email'],
                'dispute_resolved',
                'Dispute Resolved',
                "The dispute for order #{$order['id']} has been resolved",
                'emails/dispute-resolved',
                [
                    'user_name'        => $order['student_name'] ?? $order['student_email'],
                    'order_id'         => $order['id'],
                    'service_title'    => $order['service_title'],
                    'resolution'       => $this->getResolutionLabel($resolution),
                    'resolution_notes' => $resolutionNotes,
                    'order_url'        => $appUrl . '/orders/' . $order['id'],
                ],
                $appUrl . '/orders/' . $order['id']
            );

            // Insert audit log
            $this->insertAuditLog($adminId, 'dispute_resolved', 'dispute', $disputeId, [
                'order_id'         => $order['id'],
                'resolution'       => $resolution,
                'resolution_notes' => $resolutionNotes,
                'partial_amount'   => $partialAmount,
            ]);

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollBack();
            error_log('Dispute resolution error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['database' => 'Failed to resolve dispute. Please try again.'],
            ];
        }
    }

    /**
     * Release payment to student (complete order)
     *
     * @param array $order Order data
     * @param int $adminId Admin user ID
     * @return array ['success' => bool, 'errors' => array]
     */
    private function releaseToStudent(array $order, int $adminId): array
    {
        // Update order status to completed
        $this->orderRepository->update($order['id'], [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // Calculate student earnings
        $orderAmount      = (float) $order['price'];
        $commissionRate   = (float) $order['commission_rate'];
        $commissionAmount = $orderAmount * ($commissionRate / 100);
        $studentEarnings  = $orderAmount - $commissionAmount;

        // Credit student's available balance
        $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);

        // Update student profile total_orders counter
        $this->orderRepository->incrementStudentOrderCount($order['student_id']);

        return [
            'success' => true,
            'errors'  => [],
        ];
    }

    /**
     * Refund payment to client (cancel order)
     *
     * @param array $order Order data
     * @param int $adminId Admin user ID
     * @return array ['success' => bool, 'errors' => array]
     */
    private function refundToClient(array $order, int $adminId): array
    {
        // Update order status to cancelled
        $this->orderRepository->update($order['id'], [
            'status'              => 'cancelled',
            'cancelled_at'        => date('Y-m-d H:i:s'),
            'cancellation_reason' => 'Dispute resolved - full refund to client',
        ]);

        // Process full refund through PaymentService
        require_once __DIR__ . '/PaymentService.php';
        require_once __DIR__ . '/../Repositories/PaymentRepository.php';

        $paymentRepository = new PaymentRepository($this->db);
        $paymentService    = new PaymentService($paymentRepository, $this->db);

        $refundResult = $paymentService->refundPayment($order);

        if (! $refundResult['success']) {
            return $refundResult;
        }

        return [
            'success' => true,
            'errors'  => [],
        ];
    }

    /**
     * Process partial refund
     *
     * @param array $order Order data
     * @param int $adminId Admin user ID
     * @param float $refundAmount Amount to refund to client
     * @return array ['success' => bool, 'errors' => array]
     */
    private function partialRefund(array $order, int $adminId, float $refundAmount): array
    {
        require_once __DIR__ . '/PaymentService.php';
        require_once __DIR__ . '/../Repositories/PaymentRepository.php';

        $paymentRepository = new PaymentRepository($this->db);
        $paymentService    = new PaymentService($paymentRepository, $this->db);

        // Process partial refund
        $refundResult = $paymentService->refundPayment($order, $refundAmount);

        if (! $refundResult['success']) {
            return $refundResult;
        }

        // Calculate remaining amount for student
        $orderAmount      = (float) $order['price'];
        $remainingAmount  = $orderAmount - $refundAmount;
        $commissionRate   = (float) $order['commission_rate'];
        $commissionAmount = $remainingAmount * ($commissionRate / 100);
        $studentEarnings  = $remainingAmount - $commissionAmount;

        // Update order status to completed
        $this->orderRepository->update($order['id'], [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // Credit student's available balance with remaining amount
        if ($studentEarnings > 0) {
            $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);
        }

        // Update student profile total_orders counter
        $this->orderRepository->incrementStudentOrderCount($order['student_id']);

        return [
            'success' => true,
            'errors'  => [],
        ];
    }

    /**
     * Update dispute and send notifications
     *
     * @param int $disputeId Dispute ID
     * @param int $adminId Admin user ID
     * @param string $updateMessage Update message
     * @return array ['success' => bool, 'errors' => array]
     */
    public function updateDispute(int $disputeId, int $adminId, string $updateMessage): array
    {
        // Get dispute
        $dispute = $this->disputeRepository->findById($disputeId);

        if (! $dispute) {
            return [
                'success' => false,
                'errors'  => ['dispute' => 'Dispute not found'],
            ];
        }

        // Validate update message
        $updateMessage = trim($updateMessage);
        if (empty($updateMessage)) {
            return [
                'success' => false,
                'errors'  => ['update_message' => 'Update message is required'],
            ];
        }

        // Get order
        $order = $this->orderRepository->findById($dispute['order_id']);

        if (! $order) {
            return [
                'success' => false,
                'errors'  => ['order' => 'Order not found'],
            ];
        }

        try {
            // Get client, student, and service for notifications
            require_once __DIR__ . '/../Repositories/UserRepository.php';
            require_once __DIR__ . '/../Repositories/ServiceRepository.php';

            $userRepository    = new UserRepository($this->db);
            $serviceRepository = new ServiceRepository($this->db);

            $client  = $userRepository->findById($order['client_id']);
            $student = $userRepository->findById($order['student_id']);
            $service = $serviceRepository->findById($order['service_id']);

            // Send notifications to both parties
            if ($client && $student && $service) {
                $this->notificationService->notifyDisputeUpdated($dispute, $order, $client, $student, $service, $updateMessage);
            }

            // Insert audit log
            $this->insertAuditLog($adminId, 'dispute_updated', 'dispute', $disputeId, [
                'order_id'       => $order['id'],
                'update_message' => $updateMessage,
            ]);

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            error_log('Dispute update error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['database' => 'Failed to update dispute. Please try again.'],
            ];
        }
    }

    /**
     * Get human-readable resolution label
     *
     * @param string $resolution Resolution type
     * @return string
     */
    private function getResolutionLabel(string $resolution): string
    {
        $labels = [
            'release_to_student' => 'Payment Released to Student',
            'refund_to_client'   => 'Full Refund to Client',
            'partial_refund'     => 'Partial Refund',
        ];

        return $labels[$resolution] ?? $resolution;
    }

    /**
     * Insert audit log entry
     *
     * @param int $userId User ID
     * @param string $action Action performed
     * @param string $resourceType Resource type
     * @param int $resourceId Resource ID
     * @param array $data Additional data
     * @return void
     */
    private function insertAuditLog(int $userId, string $action, string $resourceType, int $resourceId, array $data): void
    {
        $sql = "INSERT INTO audit_logs (
            user_id, action, resource_type, resource_id,
            old_values, new_values, ip_address, user_agent, created_at
        ) VALUES (
            :user_id, :action, :resource_type, :resource_id,
            NULL, :new_values, :ip_address, :user_agent, NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id'       => $userId,
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'new_values'    => json_encode($data),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
