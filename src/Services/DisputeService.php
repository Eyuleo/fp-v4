<?php

require_once __DIR__ . '/../Repositories/DisputeRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Repositories/PaymentRepository.php';
require_once __DIR__ . '/OrderService.php';
require_once __DIR__ . '/PaymentService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/MailService.php';

/**
 * Dispute Service
 *
 * Handles dispute creation, investigation, and resolution
 */
class DisputeService
{
    private DisputeRepository $disputeRepository;
    private OrderRepository $orderRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private OrderService $orderService;
    private PaymentService $paymentService;
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->disputeRepository = new DisputeRepository($db);
        $this->orderRepository = new OrderRepository($db);
        $this->userRepository = new UserRepository($db);
        
        $mailService = new MailService();
        $notificationRepository = new NotificationRepository($db);
        $this->notificationService = new NotificationService($mailService, $notificationRepository);
        
        $serviceRepository = new ServiceRepository($db);
        $paymentRepository = new PaymentRepository($db);
        $this->paymentService = new PaymentService($paymentRepository, $db);
        $this->orderService = new OrderService($this->orderRepository, $serviceRepository, $this->paymentService);
    }

    /**
     * Check if a user can create a dispute for an order
     *
     * @param int $userId User attempting to create dispute
     * @param int $orderId Order ID
     * @return array ['can_create' => bool, 'reason' => string|null]
     */
    public function canUserCreateDispute(int $userId, int $orderId): array
    {
        // Get order details
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            return [
                'can_create' => false,
                'reason' => 'Order not found',
            ];
        }

        // Check if user is party to the order
        if ($order['client_id'] != $userId && $order['student_id'] != $userId) {
            return [
                'can_create' => false,
                'reason' => 'You are not authorized to create a dispute for this order',
            ];
        }

        // Check if order already has an open dispute
        if ($this->disputeRepository->hasOpenDispute($orderId)) {
            return [
                'can_create' => false,
                'reason' => 'This order already has an open dispute',
            ];
        }

        return [
            'can_create' => true,
            'reason' => null,
        ];
    }

    /**
     * Create a new dispute
     *
     * @param int $userId User creating the dispute
     * @param int $orderId Order ID
     * @param string $reason Dispute reason
     * @return array ['success' => bool, 'dispute_id' => int|null, 'errors' => array]
     */
    public function createDispute(int $userId, int $orderId, string $reason): array
    {
        // Validate required fields
        $reason = trim($reason);
        
        if (empty($reason)) {
            return [
                'success' => false,
                'dispute_id' => null,
                'errors' => ['reason' => 'Dispute reason is required'],
            ];
        }

        // Check authorization and existing disputes
        $canCreate = $this->canUserCreateDispute($userId, $orderId);
        
        if (!$canCreate['can_create']) {
            return [
                'success' => false,
                'dispute_id' => null,
                'errors' => ['authorization' => $canCreate['reason']],
            ];
        }

        // Get order and user details for notifications
        $order = $this->orderRepository->findById($orderId);
        $client = $this->userRepository->findById($order['client_id']);
        $student = $this->userRepository->findById($order['student_id']);

        try {
            // Create dispute record
            $disputeId = $this->disputeRepository->create([
                'order_id' => $orderId,
                'opened_by' => $userId,
                'reason' => $reason,
                'status' => 'open',
            ]);

            // Send notifications to other party and all admins
            $this->sendDisputeCreatedNotifications($disputeId, $order, $client, $student, $userId);

            // Log dispute creation in audit logs
            $this->insertAuditLog([
                'user_id' => $userId,
                'action' => 'dispute.created',
                'resource_type' => 'dispute',
                'resource_id' => $disputeId,
                'new_values' => json_encode([
                    'order_id' => $orderId,
                    'opened_by' => $userId,
                    'reason' => $reason,
                    'status' => 'open',
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            return [
                'success' => true,
                'dispute_id' => $disputeId,
                'errors' => [],
            ];
        } catch (Exception $e) {
            error_log('Dispute creation error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'dispute_id' => null,
                'errors' => ['database' => 'Failed to create dispute. Please try again.'],
            ];
        }
    }

    /**
     * Get dispute by ID
     *
     * @param int $disputeId Dispute ID
     * @return array|null Dispute data or null if not found
     */
    public function getDisputeById(int $disputeId): ?array
    {
        return $this->disputeRepository->findById($disputeId);
    }

    /**
     * Get all open disputes
     *
     * @return array Array of open disputes
     */
    public function getAllOpenDisputes(): array
    {
        return $this->disputeRepository->findByStatus('open');
    }

    /**
     * Resolve a dispute
     *
     * @param int $disputeId Dispute ID
     * @param int $adminId Admin user ID
     * @param array $resolutionData Resolution data (resolution, resolution_notes, refund_percentage, admin_notes)
     * @return array ['success' => bool, 'errors' => array]
     */
    public function resolveDispute(int $disputeId, int $adminId, array $resolutionData): array
    {
        // Get dispute details
        $dispute = $this->disputeRepository->findById($disputeId);
        
        if (!$dispute) {
            return [
                'success' => false,
                'errors' => ['dispute' => 'Dispute not found'],
            ];
        }

        if ($dispute['status'] !== 'open') {
            return [
                'success' => false,
                'errors' => ['status' => 'Dispute is not in open status'],
            ];
        }

        // Validate resolution type
        $validResolutionTypes = ['release_to_student', 'refund_to_client', 'partial_refund'];
        $resolution = $resolutionData['resolution'] ?? null;
        
        if (!$resolution || !in_array($resolution, $validResolutionTypes)) {
            return [
                'success' => false,
                'errors' => ['resolution' => 'Invalid resolution type. Must be one of: ' . implode(', ', $validResolutionTypes)],
            ];
        }

        // Validate resolution notes
        $resolutionNotes = trim($resolutionData['resolution_notes'] ?? '');
        
        if (empty($resolutionNotes)) {
            return [
                'success' => false,
                'errors' => ['resolution_notes' => 'Resolution notes are required'],
            ];
        }

        // Validate refund percentage for partial refunds
        if ($resolution === 'partial_refund') {
            $refundPercentage = $resolutionData['refund_percentage'] ?? null;
            
            if ($refundPercentage === null || $refundPercentage === '') {
                return [
                    'success' => false,
                    'errors' => ['refund_percentage' => 'Refund percentage is required for partial refunds'],
                ];
            }

            $refundPercentage = (float) $refundPercentage;
            
            if ($refundPercentage < 0 || $refundPercentage > 100) {
                return [
                    'success' => false,
                    'errors' => ['refund_percentage' => 'Refund percentage must be between 0 and 100'],
                ];
            }
        }

        // Get order details
        $order = $this->orderRepository->findById($dispute['order_id']);
        
        if (!$order) {
            return [
                'success' => false,
                'errors' => ['order' => 'Order not found'],
            ];
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Process resolution based on type
            switch ($resolution) {
                case 'release_to_student':
                    $result = $this->processReleaseToStudent($order, $dispute, $adminId, $resolutionData);
                    break;
                    
                case 'refund_to_client':
                    $result = $this->processRefundToClient($order, $dispute, $adminId, $resolutionData);
                    break;
                    
                case 'partial_refund':
                    $result = $this->processPartialRefund($order, $dispute, $adminId, $resolutionData);
                    break;
                    
                default:
                    throw new Exception('Invalid resolution type');
            }

            if (!$result['success']) {
                $this->db->rollBack();
                return $result;
            }

            // Update dispute status
            $this->disputeRepository->update($disputeId, [
                'status' => 'resolved',
                'resolution' => $resolution,
                'resolution_notes' => $resolutionNotes,
                'refund_percentage' => !empty($resolutionData['refund_percentage']) ? $resolutionData['refund_percentage'] : null,
                'admin_notes' => $resolutionData['admin_notes'] ?? null,
                'resolved_by' => $adminId,
                'resolved_at' => date('Y-m-d H:i:s'),
            ]);

            // Send resolution notifications
            $this->sendDisputeResolvedNotifications($disputeId, $order, $resolution, $resolutionData);

            // Log dispute resolution in audit logs
            $this->insertAuditLog([
                'user_id' => $adminId,
                'action' => 'dispute.resolved',
                'resource_type' => 'dispute',
                'resource_id' => $disputeId,
                'old_values' => json_encode([
                    'status' => 'open',
                ]),
                'new_values' => json_encode([
                    'status' => 'resolved',
                    'resolution' => $resolution,
                    'resolved_by' => $adminId,
                    'resolved_at' => date('Y-m-d H:i:s'),
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'errors' => [],
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Dispute resolution error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'errors' => ['database' => 'Failed to resolve dispute. Please try again.'],
            ];
        }
    }

    /**
     * Process release to student resolution
     *
     * @param array $order Order data
     * @param array $dispute Dispute data
     * @param int $adminId Admin user ID
     * @param array $resolutionData Resolution data
     * @return array ['success' => bool, 'errors' => array]
     */
    private function processReleaseToStudent(array $order, array $dispute, int $adminId, array $resolutionData): array
    {
        // Update order status to completed
        $this->orderRepository->update($order['id'], [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // Process payment to student
        $orderAmount = (float) $order['price'];
        $commissionRate = (float) $order['commission_rate'];
        $commissionAmount = $orderAmount * ($commissionRate / 100);
        $studentEarnings = $orderAmount - $commissionAmount;

        // Update payment status
        $paymentRepository = new PaymentRepository($this->db);
        $payment = $paymentRepository->findByOrderId($order['id']);
        
        if ($payment && $payment['status'] !== 'succeeded') {
            $paymentRepository->update($payment['id'], [
                'status' => 'succeeded',
                'commission_amount' => $commissionAmount,
                'student_amount' => $studentEarnings,
            ]);
        }

        // Add to student balance
        $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);
        $this->orderRepository->incrementStudentOrderCount($order['student_id']);

        return [
            'success' => true,
            'errors' => [],
        ];
    }

    /**
     * Process refund to client resolution
     *
     * @param array $order Order data
     * @param array $dispute Dispute data
     * @param int $adminId Admin user ID
     * @param array $resolutionData Resolution data
     * @return array ['success' => bool, 'errors' => array]
     */
    private function processRefundToClient(array $order, array $dispute, int $adminId, array $resolutionData): array
    {
        // Update order status to cancelled
        $this->orderRepository->update($order['id'], [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancellation_reason' => 'Dispute resolved in favor of client',
        ]);

        // Process refund
        $refundResult = $this->paymentService->refundPayment($order);
        
        if (!$refundResult['success']) {
            return [
                'success' => false,
                'errors' => ['refund' => 'Failed to process refund. Please try again.'],
            ];
        }

        return [
            'success' => true,
            'errors' => [],
        ];
    }

    /**
     * Process partial refund resolution
     *
     * @param array $order Order data
     * @param array $dispute Dispute data
     * @param int $adminId Admin user ID
     * @param array $resolutionData Resolution data
     * @return array ['success' => bool, 'errors' => array]
     */
    private function processPartialRefund(array $order, array $dispute, int $adminId, array $resolutionData): array
    {
        $refundPercentage = (float) $resolutionData['refund_percentage'];
        $orderAmount = (float) $order['price'];
        
        // Calculate refund and student payment amounts
        $refundAmount = $orderAmount * ($refundPercentage / 100);
        $studentPaymentAmount = $orderAmount - $refundAmount;
        
        // Calculate commission on student payment
        $commissionRate = (float) $order['commission_rate'];
        $commissionAmount = $studentPaymentAmount * ($commissionRate / 100);
        $studentEarnings = $studentPaymentAmount - $commissionAmount;

        // Update order status to completed
        $this->orderRepository->update($order['id'], [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // Process partial refund
        $refundResult = $this->paymentService->refundPayment($order, $refundAmount);
        
        if (!$refundResult['success']) {
            return [
                'success' => false,
                'errors' => ['refund' => 'Failed to process partial refund. Please try again.'],
            ];
        }

        // Update payment record with partial refund details
        $paymentRepository = new PaymentRepository($this->db);
        $payment = $paymentRepository->findByOrderId($order['id']);
        
        if ($payment) {
            $paymentRepository->update($payment['id'], [
                'status' => 'partially_refunded',
                'commission_amount' => $commissionAmount,
                'student_amount' => $studentEarnings,
            ]);
        }

        // Add student earnings to balance
        $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);
        $this->orderRepository->incrementStudentOrderCount($order['student_id']);

        return [
            'success' => true,
            'errors' => [],
        ];
    }

    /**
     * Send dispute created notifications
     *
     * @param int $disputeId Dispute ID
     * @param array $order Order data
     * @param array $client Client user data
     * @param array $student Student user data
     * @param int $openedBy User ID who opened the dispute
     * @return void
     */
    private function sendDisputeCreatedNotifications(int $disputeId, array $order, array $client, array $student, int $openedBy): void
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';
        $dispute = $this->disputeRepository->findById($disputeId);
        
        if (!$dispute) {
            return;
        }

        // Determine who opened the dispute
        $opener = ($openedBy == $client['id']) ? $client : $student;
        $otherParty = ($openedBy == $client['id']) ? $student : $client;

        // Notify the other party
        try {
            $this->notificationService->notify(
                $otherParty['id'],
                $otherParty['email'],
                'dispute_created',
                'Dispute Opened',
                "A dispute has been opened for order #{$order['id']}",
                'emails/dispute-created',
                [
                    'recipient_name' => $otherParty['name'] ?? $otherParty['email'],
                    'opener_name' => $opener['name'] ?? $opener['email'],
                    'order_id' => $order['id'],
                    'service_title' => $order['service_title'] ?? 'Service',
                    'reason' => $dispute['reason'],
                    'dispute_url' => $appUrl . '/orders/' . $order['id'],
                ],
                $appUrl . '/orders/' . $order['id']
            );
        } catch (Exception $e) {
            error_log('Failed to send dispute notification to other party: ' . $e->getMessage());
        }

        // Notify all admins
        try {
            $admins = $this->userRepository->getAllAdmins();
            
            foreach ($admins as $admin) {
                $this->notificationService->notify(
                    $admin['id'],
                    $admin['email'],
                    'dispute_created',
                    'New Dispute Opened',
                    "A dispute has been opened for order #{$order['id']}",
                    'emails/dispute-created',
                    [
                        'recipient_name' => $admin['name'] ?? $admin['email'],
                        'opener_name' => $opener['name'] ?? $opener['email'],
                        'order_id' => $order['id'],
                        'service_title' => $order['service_title'] ?? 'Service',
                        'reason' => $dispute['reason'],
                        'dispute_url' => $appUrl . '/admin/disputes/' . $disputeId,
                    ],
                    $appUrl . '/admin/disputes/' . $disputeId
                );
            }
        } catch (Exception $e) {
            error_log('Failed to send dispute notifications to admins: ' . $e->getMessage());
        }
    }

    /**
     * Send dispute resolved notifications
     *
     * @param int $disputeId Dispute ID
     * @param array $order Order data
     * @param string $resolution Resolution type
     * @param array $resolutionData Resolution data
     * @return void
     */
    private function sendDisputeResolvedNotifications(int $disputeId, array $order, string $resolution, array $resolutionData): void
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';
        $dispute = $this->disputeRepository->findById($disputeId);
        
        if (!$dispute) {
            return;
        }

        $client = $this->userRepository->findById($order['client_id']);
        $student = $this->userRepository->findById($order['student_id']);

        if (!$client || !$student) {
            return;
        }

        // Calculate financial outcomes
        $orderAmount = (float) $order['price'];
        $refundAmount = 0;
        $studentPayment = 0;

        switch ($resolution) {
            case 'release_to_student':
                $commissionRate = (float) $order['commission_rate'];
                $studentPayment = $orderAmount - ($orderAmount * ($commissionRate / 100));
                break;
                
            case 'refund_to_client':
                $refundAmount = $orderAmount;
                break;
                
            case 'partial_refund':
                $refundPercentage = (float) $resolutionData['refund_percentage'];
                $refundAmount = $orderAmount * ($refundPercentage / 100);
                $studentPaymentAmount = $orderAmount - $refundAmount;
                $commissionRate = (float) $order['commission_rate'];
                $studentPayment = $studentPaymentAmount - ($studentPaymentAmount * ($commissionRate / 100));
                break;
        }

        // Notify client
        try {
            $this->notificationService->notify(
                $client['id'],
                $client['email'],
                'dispute_resolved',
                'Dispute Resolved',
                "The dispute for order #{$order['id']} has been resolved",
                'emails/dispute-resolved',
                [
                    'recipient_name' => $client['name'] ?? $client['email'],
                    'order_id' => $order['id'],
                    'service_title' => $order['service_title'] ?? 'Service',
                    'resolution' => $resolution,
                    'resolution_notes' => $resolutionData['resolution_notes'],
                    'refund_amount' => $refundAmount,
                    'student_payment' => $studentPayment,
                    'order_url' => $appUrl . '/orders/' . $order['id'],
                ],
                $appUrl . '/orders/' . $order['id']
            );
        } catch (Exception $e) {
            error_log('Failed to send dispute resolution notification to client: ' . $e->getMessage());
        }

        // Notify student
        try {
            $this->notificationService->notify(
                $student['id'],
                $student['email'],
                'dispute_resolved',
                'Dispute Resolved',
                "The dispute for order #{$order['id']} has been resolved",
                'emails/dispute-resolved',
                [
                    'recipient_name' => $student['name'] ?? $student['email'],
                    'order_id' => $order['id'],
                    'service_title' => $order['service_title'] ?? 'Service',
                    'resolution' => $resolution,
                    'resolution_notes' => $resolutionData['resolution_notes'],
                    'refund_amount' => $refundAmount,
                    'student_payment' => $studentPayment,
                    'order_url' => $appUrl . '/orders/' . $order['id'],
                ],
                $appUrl . '/orders/' . $order['id']
            );
        } catch (Exception $e) {
            error_log('Failed to send dispute resolution notification to student: ' . $e->getMessage());
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
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'],
            'resource_type' => $data['resource_type'],
            'resource_id' => $data['resource_id'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }
}
