<?php

require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Repositories/PaymentRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';
require_once __DIR__ . '/../Repositories/MessageRepository.php';
require_once __DIR__ . '/../Validators/OrderValidator.php';
require_once __DIR__ . '/PaymentService.php';
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/FileService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/../Repositories/DisputeRepository.php';

class OrderService
{
    private OrderRepository $orderRepository;
    private ServiceRepository $serviceRepository;
    private UserRepository $userRepository;
    private OrderValidator $validator;
    private PaymentService $paymentService;
    private EmailService $emailService;
    private FileService $fileService;
    private NotificationService $notificationService;
    private MessageRepository $messageRepository;
    private DisputeRepository $disputeRepository;
    private PDO $db;

    public function __construct(OrderRepository $orderRepository, ServiceRepository $serviceRepository, PaymentService $paymentService = null)
    {
        $this->orderRepository   = $orderRepository;
        $this->serviceRepository = $serviceRepository;
        $this->validator         = new OrderValidator();
        $this->emailService      = new EmailService();
        $this->fileService       = new FileService();

        $db                        = $orderRepository->getDb();
        $this->db                  = $db;
        $this->userRepository      = new UserRepository($db);
        $this->messageRepository   = new MessageRepository($db);
        $mailService               = new MailService();
        $notificationRepository    = new NotificationRepository($db);
        $this->notificationService = new NotificationService($mailService, $notificationRepository);
        $this->disputeRepository   = new DisputeRepository($db);

        if ($paymentService === null) {
            $paymentRepository    = new PaymentRepository($db);
            $this->paymentService = new PaymentService($paymentRepository, $db);
        } else {
            $this->paymentService = $paymentService;
        }
    }

    private function getDefaultReviewWindowHours(): int
    {
        $hours = getenv('ORDER_REVIEW_WINDOW_HOURS');
        return ($hours && (int) $hours > 0) ? (int) $hours : 24;
    }

    public function createOrder(int $clientId, int $serviceId, array $data): array
    {
        // Check if client has active suspension
        $suspensionStatus = $this->userRepository->checkSuspensionStatus($clientId);
        if ($suspensionStatus['is_suspended']) {
            $errorMessage = 'Your account is currently suspended and you cannot place orders.';
            if ($suspensionStatus['suspension_end_date']) {
                $errorMessage .= ' Your suspension will end on ' . date('F j, Y', strtotime($suspensionStatus['suspension_end_date'])) . '.';
            }
            return [
                'success'  => false,
                'order_id' => null,
                'order'    => null,
                'errors'   => ['suspension' => $errorMessage],
            ];
        }

        if (! $this->validator->validateCreate($data)) {
            return [
                'success'  => false,
                'order_id' => null,
                'order'    => null,
                'errors'   => $this->validator->getErrors(),
            ];
        }

        $service = $this->serviceRepository->findById($serviceId);

        if (! $service) {
            return [
                'success'  => false,
                'order_id' => null,
                'order'    => null,
                'errors'   => ['service' => 'Service not found'],
            ];
        }

        if ($service['status'] !== 'active') {
            return [
                'success'  => false,
                'order_id' => null,
                'order'    => null,
                'errors'   => ['service' => 'Service is not available'],
            ];
        }

        if (! empty($data['files'])) {
            if (! $this->validator->validateFiles($data['files'])) {
                return [
                    'success'  => false,
                    'order_id' => null,
                    'order'    => null,
                    'errors'   => $this->validator->getErrors(),
                ];
            }
        }

        $commissionRate = $this->orderRepository->getCommissionRate();
        $maxRevisions   = $this->orderRepository->getMaxRevisions();
        
        // Fix: Use DateTime with DateInterval for accurate deadline calculation
        $config = require __DIR__ . '/../../config/app.php';
        $timezone = new DateTimeZone($config['timezone']);
        $deadline = (new DateTime('now', $timezone))
            ->add(new DateInterval('P' . $service['delivery_days'] . 'D'))
            ->format('Y-m-d H:i:s');

        $orderData = [
            'client_id'         => $clientId,
            'student_id'        => $service['student_id'],
            'service_id'        => $serviceId,
            'status'            => 'in_progress',
            'requirements'      => trim($data['requirements']),
            'requirement_files' => [],
            'price'             => $service['price'],
            'commission_rate'   => $commissionRate,
            'deadline'          => $deadline,
            'max_revisions'     => $maxRevisions,
        ];

        $orderId = $this->orderRepository->create($orderData);

        if (! empty($data['files']) && $orderId) {
            $uploadedFiles = $this->handleFileUploads($orderId, $data['files']);
            if (! empty($uploadedFiles)) {
                $this->orderRepository->update($orderId, [
                    'requirement_files' => $uploadedFiles,
                ]);
            }
        }

        $order = $this->orderRepository->findById($orderId);

        try {
            $client  = $this->userRepository->findById($clientId);
            $student = $this->userRepository->findById($service['student_id']);

            if ($client && $student && $order) {
                $this->notificationService->notifyOrderPlaced($order, $student, $client, $service);
            }
        } catch (Exception $e) {
            error_log('Failed to send order placed notification: ' . $e->getMessage());
        }

        return [
            'success'  => true,
            'order_id' => $orderId,
            'order'    => $order,
            'errors'   => [],
        ];
    }

    public function getOrderById(int $orderId): ?array
    {
        return $this->orderRepository->findById($orderId);
    }

    public function getOrdersForClient(int $clientId, ?string $status = null): array
    {
        return $this->orderRepository->findByClientId($clientId, $status);
    }

    public function getOrdersForStudent(int $studentId, ?string $status = null): array
    {
        return $this->orderRepository->findByStudentId($studentId, $status);
    }

    public function acceptOrder(int $orderId, int $studentId): array
    {
        $order = $this->orderRepository->findById($orderId);
        if (! $order) {
            return ['success' => false, 'errors' => ['order' => 'Order not found']];
        }
        if ($order['student_id'] != $studentId) {
            return ['success' => false, 'errors' => ['authorization' => 'You are not authorized to accept this order']];
        }
        if ($order['status'] !== 'pending') {
            return ['success' => false, 'errors' => ['status' => 'Order cannot be accepted in its current status']];
        }

        $this->orderRepository->beginTransaction();
        try {
            $this->orderRepository->update($orderId, ['status' => 'in_progress']);

            try {
                $client  = $this->userRepository->findById($order['client_id']);
                $student = $this->userRepository->findById($order['student_id']);
                $service = $this->serviceRepository->findById($order['service_id']);
                if ($client && $student && $service) {
                    $updatedOrder = $this->orderRepository->findById($orderId);
                    $this->notificationService->notifyOrderAccepted($updatedOrder, $client, $student, $service);
                }
            } catch (Exception $e) {
                error_log('Failed to send order accepted notification: ' . $e->getMessage());
            }

            $this->orderRepository->commit();
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            $this->orderRepository->rollback();
            error_log('Order acceptance error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['database' => 'Failed to accept order. Please try again.']];
        }
    }

    /**
     * Ensure review window fields exist for a delivered order; backfill if missing.
     */
    public function ensureReviewWindow(array $order): array
    {
        if (($order['status'] ?? '') !== 'delivered') {
            return $order;
        }
        if (! empty($order['review_deadline'])) {
            return $order;
        }

        $deliveredAt       = $order['delivered_at'] ?? $order['updated_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s');
        $reviewWindowHours = $order['review_window_hours'] ?? $this->getDefaultReviewWindowHours();
        $reviewDeadlineTs  = strtotime($deliveredAt) + ($reviewWindowHours * 3600);
        $reviewDeadline    = date('Y-m-d H:i:s', $reviewDeadlineTs);

        $this->orderRepository->update((int) $order['id'], [
            'delivered_at'        => $deliveredAt,
            'review_window_hours' => $reviewWindowHours,
            'review_deadline'     => $reviewDeadline,
        ]);

        return $this->orderRepository->findById((int) $order['id']);
    }

    public function deliverOrder(int $orderId, int $studentId, array $data): array
    {
        $order = $this->orderRepository->findById($orderId);
        if (! $order) {
            return ['success' => false, 'errors' => ['order' => 'Order not found']];
        }
        if ($order['student_id'] != $studentId) {
            return ['success' => false, 'errors' => ['authorization' => 'You are not authorized to deliver this order']];
        }
        if (! in_array($order['status'], ['in_progress', 'revision_requested'])) {
            return ['success' => false, 'errors' => ['status' => 'Order cannot be delivered in its current status']];
        }

        if (! empty($order['deadline']) && time() > strtotime($order['deadline'])) {
            return ['success' => false, 'errors' => ['deadline' => 'This order is past its deadline. Please contact an administrator.']];
        }

        if ($this->disputeRepository->hasOpenDispute($orderId)) {
            return ['success' => false, 'errors' => ['dispute' => 'Order is currently in dispute. Actions are paused until resolved.']];
        }

        $deliveryMessage = trim($data['delivery_message'] ?? '');
        if ($deliveryMessage === '') {
            return ['success' => false, 'errors' => ['delivery_message' => 'Delivery message is required']];
        }
        if (empty($data['files'])) {
            return ['success' => false, 'errors' => ['files' => 'At least one delivery file is required']];
        }
        if (! $this->validator->validateFiles($data['files'])) {
            return ['success' => false, 'errors' => $this->validator->getErrors()];
        }

        // Compute review window fields
        $reviewWindowHours = $this->getDefaultReviewWindowHours();
        $deliveredAtTs     = time();
        $deadlineTs        = strtotime($order['deadline']);
        $minWindowHours    = (int) (getenv('ORDER_MIN_REVIEW_WINDOW_HOURS') ?: 12);

        $baseReviewDeadlineTs = $deliveredAtTs + ($reviewWindowHours * 3600);
        if (($deadlineTs - $deliveredAtTs) < ($minWindowHours * 3600)) {
            $baseReviewDeadlineTs = max($baseReviewDeadlineTs, $deliveredAtTs + ($minWindowHours * 3600));
        }

        $deliveredAt    = date('Y-m-d H:i:s', $deliveredAtTs);
        $reviewDeadline = date('Y-m-d H:i:s', $baseReviewDeadlineTs);

        $this->orderRepository->beginTransaction();
        try {
            $uploadedFiles = $this->handleDeliveryFileUploads($orderId, $data['files']);
            if (empty($uploadedFiles)) {
                throw new Exception('Failed to upload delivery files');
            }

            // Calculate delivery number (current count + 1)
            $currentDeliveryCount = $order['delivery_count'] ?? 0;
            $newDeliveryNumber = $currentDeliveryCount + 1;
            
            // Mark all previous deliveries as not current
            $this->orderRepository->markAllDeliveriesNotCurrent($orderId);
            
            // Create new delivery history entry
            $deliveryHistoryId = $this->orderRepository->createDeliveryHistory(
                $orderId,
                $deliveryMessage,
                $uploadedFiles,
                $newDeliveryNumber,
                true // is_current
            );

            // Update order with delivery info and history references
            $this->orderRepository->update($orderId, [
                'status'              => 'delivered',
                'delivery_message'    => $deliveryMessage,
                'delivery_files'      => $uploadedFiles,
                'delivered_at'        => $deliveredAt,
                'review_window_hours' => $reviewWindowHours,
                'review_deadline'     => $reviewDeadline,
            ]);
            
            // Update current_delivery_id and delivery_count
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET current_delivery_id = :delivery_id, 
                    delivery_count = :delivery_count 
                WHERE id = :order_id
            ");
            $stmt->execute([
                'delivery_id'    => $deliveryHistoryId,
                'delivery_count' => $newDeliveryNumber,
                'order_id'       => $orderId,
            ]);

            try {
                $client  = $this->userRepository->findById($order['client_id']);
                $student = $this->userRepository->findById($order['student_id']);
                $service = $this->serviceRepository->findById($order['service_id']);
                if ($client && $student && $service) {
                    $updatedOrder = $this->orderRepository->findById($orderId);
                    $this->notificationService->notifyOrderDelivered($updatedOrder, $client, $student, $service);
                }
            } catch (Exception $e) {
                error_log('Failed to send order delivered notification: ' . $e->getMessage());
            }

            $this->orderRepository->commit();
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            $this->orderRepository->rollback();
            error_log('Order delivery error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['database' => 'Failed to deliver order. Please try again.']];
        }
    }

    public function completeOrder(int $orderId, int $clientId): array
    {
        $order = $this->orderRepository->findById($orderId);
        if (! $order) {
            return ['success' => false, 'errors' => ['order' => 'Order not found']];
        }
        if ($order['client_id'] != $clientId) {
            return ['success' => false, 'errors' => ['authorization' => 'You are not authorized to complete this order']];
        }
        if ($order['status'] !== 'delivered') {
            return ['success' => false, 'errors' => ['status' => 'Order cannot be completed in its current status']];
        }

        if ($this->disputeRepository->hasOpenDispute($orderId)) {
            return ['success' => false, 'errors' => ['dispute' => 'Order is currently in dispute. Actions are paused until resolved.']];
        }

        $this->orderRepository->beginTransaction();
        try {
            // Verify payment status before proceeding
            $paymentRepository = new PaymentRepository($this->db);
            $payment = $paymentRepository->findByOrderId($orderId);
            
            if (!$payment) {
                $this->orderRepository->rollback();
                error_log(json_encode([
                    'error'     => 'Payment not found for order completion',
                    'order_id'  => $orderId,
                    'timestamp' => date('Y-m-d H:i:s'),
                ]));
                return ['success' => false, 'errors' => ['payment' => 'Payment record not found. Please contact support.']];
            }

            if ($payment['status'] !== 'succeeded') {
                $this->orderRepository->rollback();
                error_log(json_encode([
                    'error'          => 'Cannot complete order with unconfirmed payment',
                    'order_id'       => $orderId,
                    'payment_id'     => $payment['id'],
                    'payment_status' => $payment['status'],
                    'timestamp'      => date('Y-m-d H:i:s'),
                ]));
                return ['success' => false, 'errors' => ['payment' => 'Payment not confirmed. Cannot complete order.']];
            }

            $orderAmount      = (float) $order['price'];
            $commissionRate   = (float) $order['commission_rate'];
            $commissionAmount = $orderAmount * ($commissionRate / 100);
            $studentEarnings  = $orderAmount - $commissionAmount;

            // Get student's current balance before update
            $studentProfile = $this->orderRepository->getDb()->prepare(
                "SELECT available_balance FROM student_profiles WHERE user_id = :student_id"
            );
            $studentProfile->execute(['student_id' => $order['student_id']]);
            $profileData = $studentProfile->fetch(PDO::FETCH_ASSOC);
            $previousBalance = $profileData ? (float) $profileData['available_balance'] : 0.00;

            $this->orderRepository->update($orderId, [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            // Update payment commission amounts if not already set
            if ($payment['commission_amount'] == 0 || $payment['student_amount'] == 0) {
                $paymentRepository->update($payment['id'], [
                    'commission_amount' => $commissionAmount,
                    'student_amount'    => $studentEarnings,
                ]);
            }

            // Update student balance
            $balanceUpdateSuccess = $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);
            
            if (!$balanceUpdateSuccess) {
                throw new Exception('Failed to update student balance');
            }

            $newBalance = $previousBalance + $studentEarnings;

            // Log balance update for audit trail
            error_log(json_encode([
                'event'            => 'balance_updated',
                'order_id'         => $orderId,
                'student_id'       => $order['student_id'],
                'previous_balance' => $previousBalance,
                'change_amount'    => $studentEarnings,
                'new_balance'      => $newBalance,
                'commission'       => $commissionAmount,
                'timestamp'        => date('Y-m-d H:i:s'),
            ]));

            // Create audit log entry for balance transaction
            $this->createBalanceAuditLog(
                $order['student_id'],
                $orderId,
                $previousBalance,
                $studentEarnings,
                $newBalance,
                $commissionAmount
            );

            $this->orderRepository->incrementStudentOrderCount($order['student_id']);

            try {
                $client  = $this->userRepository->findById($order['client_id']);
                $student = $this->userRepository->findById($order['student_id']);
                $service = $this->serviceRepository->findById($order['service_id']);
                if ($client && $student && $service) {
                    $updatedOrder = $this->orderRepository->findById($orderId);
                    $this->notificationService->notifyOrderCompleted($updatedOrder, $client, $student, $service, $studentEarnings);
                }
            } catch (Exception $e) {
                error_log('Failed to send order completed notification: ' . $e->getMessage());
            }

            $this->orderRepository->commit();
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            $this->orderRepository->rollback();
            error_log(json_encode([
                'error'     => 'Order completion error',
                'order_id'  => $orderId,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s'),
            ]));
            return ['success' => false, 'errors' => ['database' => 'Failed to complete order. Please try again.']];
        }
    }

    public function requestRevision(int $orderId, int $clientId, string $reason): array
    {
        $order = $this->orderRepository->findById($orderId);
        if (! $order) {
            return ['success' => false, 'errors' => ['order' => 'Order not found']];
        }
        if ($order['client_id'] != $clientId) {
            return ['success' => false, 'errors' => ['authorization' => 'You are not authorized to request revision on this order']];
        }
        if ($order['status'] !== 'delivered') {
            return ['success' => false, 'errors' => ['status' => 'Order must be in delivered status to request revision']];
        }

        $maxRevisions = $order['max_revisions'] ?? 3;
        if (($order['revision_count'] ?? 0) >= $maxRevisions) {
            return ['success' => false, 'errors' => ['revision_limit' => 'Maximum number of revisions reached. Please open a dispute if needed.']];
        }

        if ($this->disputeRepository->hasOpenDispute($orderId)) {
            return ['success' => false, 'errors' => ['dispute' => 'Order is currently in dispute. Actions are paused until resolved.']];
        }

        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'errors' => ['revision_reason' => 'Please provide a reason for the revision request']];
        }

        $this->orderRepository->beginTransaction();
        try {
            $newRevisionCount = ($order['revision_count'] ?? 0) + 1;
            
            // Mark all previous revisions as not current
            $stmt = $this->db->prepare("UPDATE order_revision_history SET is_current = 0 WHERE order_id = :order_id");
            $stmt->execute(['order_id' => $orderId]);
            
            // Create new revision history entry
            $stmt = $this->db->prepare("
                INSERT INTO order_revision_history (order_id, revision_reason, requested_by, revision_number, is_current)
                VALUES (:order_id, :revision_reason, :requested_by, :revision_number, 1)
            ");
            $stmt->execute([
                'order_id'        => $orderId,
                'revision_reason' => $reason,
                'requested_by'    => $clientId,
                'revision_number' => $newRevisionCount,
            ]);
            
            $revisionHistoryId = (int) $this->db->lastInsertId();
            
            // Update order with new revision info
            $this->orderRepository->update($orderId, [
                'status'                 => 'revision_requested',
                'revision_count'         => $newRevisionCount,
                'revision_reason'        => $reason,
            ]);
            
            // Update current_revision_id and revision_history_count
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET current_revision_id = :revision_id, 
                    revision_history_count = :history_count 
                WHERE id = :order_id
            ");
            $stmt->execute([
                'revision_id'   => $revisionHistoryId,
                'history_count' => $newRevisionCount,
                'order_id'      => $orderId,
            ]);

            try {
                $student = $this->userRepository->findById($order['student_id']);
                $service = $this->serviceRepository->findById($order['service_id']);
                if ($student && $service) {
                    $updatedOrder = $this->orderRepository->findById($orderId);
                    $this->notificationService->notifyRevisionRequested($updatedOrder, $student, $service, $reason);
                }
            } catch (Exception $e) {
                error_log('Failed to send revision requested notification: ' . $e->getMessage());
            }

            $this->orderRepository->commit();
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            $this->orderRepository->rollback();
            error_log('Revision request error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['database' => 'Failed to request revision. Please try again.']];
        }
    }

    public function cancelOrder(int $orderId, array $user, string $reason): array
    {
        if (! isset($user['role']) || $user['role'] !== 'admin') {
            return ['success' => false, 'errors' => ['authorization' => 'Only administrators can cancel orders']];
        }
        $order = $this->orderRepository->findById($orderId);
        if (! $order) {
            return ['success' => false, 'errors' => ['order' => 'Order not found']];
        }

        $reason = trim($reason);
        if ($reason === '') {
            $reason = 'Order cancelled by administrator';
        }

        $refundResult = $this->paymentService->refundPayment($order);
        if (! $refundResult['success']) {
            error_log(json_encode([
                'error'     => 'Refund failed during order cancellation',
                'order_id'  => $orderId,
                'admin_id'  => $user['id'],
                'errors'    => $refundResult['errors'],
                'timestamp' => date('Y-m-d H:i:s'),
            ]));
            return ['success' => false, 'errors' => ['refund' => 'Failed to process refund. Please contact support.']];
        }

        $this->orderRepository->beginTransaction();
        try {
            $this->orderRepository->update($orderId, [
                'status'              => 'cancelled',
                'cancelled_at'        => date('Y-m-d H:i:s'),
                'cancellation_reason' => $reason,
            ]);

            try {
                $client  = $this->userRepository->findById($order['client_id']);
                $student = $this->userRepository->findById($order['student_id']);
                $service = $this->serviceRepository->findById($order['service_id']);
                if ($client && $student && $service) {
                    $updatedOrder = $this->orderRepository->findById($orderId);
                    $this->notificationService->notifyOrderCancelled($updatedOrder, $client, $student, $service);
                }
            } catch (Exception $e) {
                error_log('Failed to send order cancelled notification: ' . $e->getMessage());
            }

            $this->orderRepository->commit();
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            $this->orderRepository->rollback();
            error_log('Order cancellation error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['database' => 'Failed to cancel order. Please try again.']];
        }
    }

    /**
     * PATCH: Admin force completion of delivered order based on review window expiry.
     * Conditions:
     * - Admin role
     * - Order status = delivered
     * - Current time > review_deadline
     */
    public function adminForceComplete(int $orderId, array $adminUser, string $reason = ''): array
    {
        if (($adminUser['role'] ?? '') !== 'admin') {
            return ['success' => false, 'errors' => ['authorization' => 'Only administrators can force-complete orders']];
        }

        $order = $this->orderRepository->findById($orderId);
        if (! $order) {
            return ['success' => false, 'errors' => ['order' => 'Order not found']];
        }

        if ($order['status'] !== 'delivered') {
            return ['success' => false, 'errors' => ['status' => 'Order must be in delivered status to force completion']];
        }

        if (empty($order['review_deadline']) || time() <= strtotime($order['review_deadline'])) {
            return ['success' => false, 'errors' => ['review_window' => 'Review window has not expired; cannot force completion']];
        }

        $this->orderRepository->beginTransaction();
        try {
            // Verify payment status before proceeding
            $paymentRepository = new PaymentRepository($this->db);
            $payment = $paymentRepository->findByOrderId($orderId);
            
            if (!$payment) {
                $this->orderRepository->rollback();
                error_log(json_encode([
                    'error'     => 'Payment not found for admin force completion',
                    'order_id'  => $orderId,
                    'admin_id'  => $adminUser['id'],
                    'timestamp' => date('Y-m-d H:i:s'),
                ]));
                return ['success' => false, 'errors' => ['payment' => 'Payment record not found. Please contact support.']];
            }

            if ($payment['status'] !== 'succeeded') {
                $this->orderRepository->rollback();
                error_log(json_encode([
                    'error'          => 'Cannot force complete order with unconfirmed payment',
                    'order_id'       => $orderId,
                    'payment_id'     => $payment['id'],
                    'payment_status' => $payment['status'],
                    'admin_id'       => $adminUser['id'],
                    'timestamp'      => date('Y-m-d H:i:s'),
                ]));
                return ['success' => false, 'errors' => ['payment' => 'Payment not confirmed. Cannot complete order.']];
            }

            $orderAmount      = (float) $order['price'];
            $commissionRate   = (float) $order['commission_rate'];
            $commissionAmount = $orderAmount * ($commissionRate / 100);
            $studentEarnings  = $orderAmount - $commissionAmount;

            // Get student's current balance before update
            $studentProfile = $this->orderRepository->getDb()->prepare(
                "SELECT available_balance FROM student_profiles WHERE user_id = :student_id"
            );
            $studentProfile->execute(['student_id' => $order['student_id']]);
            $profileData = $studentProfile->fetch(PDO::FETCH_ASSOC);
            $previousBalance = $profileData ? (float) $profileData['available_balance'] : 0.00;

            $this->orderRepository->update($orderId, [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            // Update payment commission amounts if not already set
            if ($payment['commission_amount'] == 0 || $payment['student_amount'] == 0) {
                $paymentRepository->update($payment['id'], [
                    'commission_amount' => $commissionAmount,
                    'student_amount'    => $studentEarnings,
                ]);
            }

            // Update student balance
            $balanceUpdateSuccess = $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);
            
            if (!$balanceUpdateSuccess) {
                throw new Exception('Failed to update student balance');
            }

            $newBalance = $previousBalance + $studentEarnings;

            // Log balance update for audit trail
            error_log(json_encode([
                'event'            => 'balance_updated_admin_force',
                'order_id'         => $orderId,
                'student_id'       => $order['student_id'],
                'admin_id'         => $adminUser['id'],
                'previous_balance' => $previousBalance,
                'change_amount'    => $studentEarnings,
                'new_balance'      => $newBalance,
                'commission'       => $commissionAmount,
                'timestamp'        => date('Y-m-d H:i:s'),
            ]));

            // Create audit log entry for balance transaction
            $this->createBalanceAuditLog(
                $order['student_id'],
                $orderId,
                $previousBalance,
                $studentEarnings,
                $newBalance,
                $commissionAmount,
                'admin_force_complete',
                $adminUser['id']
            );

            $this->orderRepository->incrementStudentOrderCount($order['student_id']);

            try {
                $client  = $this->userRepository->findById($order['client_id']);
                $student = $this->userRepository->findById($order['student_id']);
                $service = $this->serviceRepository->findById($order['service_id']);
                if ($client && $student && $service) {
                    $updatedOrder = $this->orderRepository->findById($orderId);
                    $this->notificationService->notifyOrderCompleted($updatedOrder, $client, $student, $service, $studentEarnings, true);
                }
            } catch (Exception $e) {
                error_log('Failed to send force-completed notification: ' . $e->getMessage());
            }

            $this->orderRepository->commit();
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            $this->orderRepository->rollback();
            error_log(json_encode([
                'error'     => 'Admin force completion error',
                'order_id'  => $orderId,
                'admin_id'  => $adminUser['id'],
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s'),
            ]));
            return ['success' => false, 'errors' => ['database' => 'Failed to force complete order. Please try again.']];
        }
    }

    /**
     * Auto-complete all delivered orders whose review window has expired.
     * Note: This method uses a direct query via $this->db to avoid repository changes.
     */
    public function autoCompleteExpired(): array
    {
        $stmt = $this->db->prepare("
            SELECT id FROM orders
            WHERE status = 'delivered'
              AND review_deadline IS NOT NULL
              AND review_deadline < NOW()
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $results[$row['id']] = $this->completeOrderSystem((int) $row['id']);
        }
        return $results;
    }

    /**
     * Internal helper for system-triggered completion (auto).
     */
    private function completeOrderSystem(int $orderId): array
    {
        $order = $this->orderRepository->findById($orderId);
        if (! $order || $order['status'] !== 'delivered') {
            return ['success' => false, 'errors' => ['status' => 'Order not in delivered status']];
        }
        if (empty($order['review_deadline']) || time() < strtotime($order['review_deadline'])) {
            return ['success' => false, 'errors' => ['review_deadline' => 'Review window not expired']];
        }

        $this->orderRepository->beginTransaction();
        try {
            $orderAmount      = (float) $order['price'];
            $commissionRate   = (float) $order['commission_rate'];
            $commissionAmount = $orderAmount * ($commissionRate / 100);
            $studentEarnings  = $orderAmount - $commissionAmount;

            $this->orderRepository->update($orderId, [
                'status'            => 'completed',
                'completed_at'      => date('Y-m-d H:i:s'),
                'auto_completed_at' => date('Y-m-d H:i:s'),
            ]);

            // Update payment status to succeeded and record commission amounts
            $paymentRepository = new PaymentRepository($this->db);
            $payment = $paymentRepository->findByOrderId($orderId);
            if ($payment && $payment['status'] !== 'succeeded') {
                $paymentRepository->update($payment['id'], [
                    'status'            => 'succeeded',
                    'commission_amount' => $commissionAmount,
                    'student_amount'    => $studentEarnings,
                ]);
            }

            $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);
            $this->orderRepository->incrementStudentOrderCount($order['student_id']);

            try {
                $client  = $this->userRepository->findById($order['client_id']);
                $student = $this->userRepository->findById($order['student_id']);
                $service = $this->serviceRepository->findById($order['service_id']);
                if ($client && $student && $service) {
                    $updatedOrder = $this->orderRepository->findById($orderId);
                    $this->notificationService->notifyOrderCompleted(
                        $updatedOrder,
                        $client,
                        $student,
                        $service,
                        $studentEarnings,
                        true
                    );
                }
            } catch (Exception $e) {
                error_log('Failed to send auto-completed notification: ' . $e->getMessage());
            }

            $this->orderRepository->commit();
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            $this->orderRepository->rollback();
            error_log('Auto completion error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['database' => 'Failed to auto-complete order']];
        }
    }

    private function handleFileUploads(int $orderId, array $files): array
    {
        if (empty($files)) {
            return [];
        }
        $result = $this->fileService->uploadMultiple($files, 'orders/requirements', $orderId);
        if (! $result['success'] && ! empty($result['errors'])) {
            throw new Exception('File upload failed: ' . implode(', ', $result['errors']));
        }
        return $result['files'];
    }

    private function handleDeliveryFileUploads(int $orderId, array $files): array
    {
        if (empty($files)) {
            return [];
        }
        $result = $this->fileService->uploadMultiple($files, 'orders/delivery', $orderId);
        if (! $result['success'] && ! empty($result['errors'])) {
            throw new Exception('File upload failed: ' . implode(', ', $result['errors']));
        }
        return $result['files'];
    }

    /**
     * Create audit log entry for balance transaction
     *
     * @param int $studentId Student user ID
     * @param int $orderId Order ID
     * @param float $previousBalance Previous balance amount
     * @param float $changeAmount Amount added to balance
     * @param float $newBalance New balance amount
     * @param float $commissionAmount Commission deducted
     * @param string $actionType Type of completion action (default: 'order_complete')
     * @param int|null $adminId Admin user ID if admin-triggered
     * @return void
     */
    private function createBalanceAuditLog(
        int $studentId,
        int $orderId,
        float $previousBalance,
        float $changeAmount,
        float $newBalance,
        float $commissionAmount,
        string $actionType = 'order_complete',
        ?int $adminId = null
    ): void {
        try {
            $oldValues = [
                'available_balance' => $previousBalance,
            ];

            $newValues = [
                'available_balance' => $newBalance,
                'change_amount'     => $changeAmount,
                'commission'        => $commissionAmount,
                'order_id'          => $orderId,
            ];

            if ($adminId !== null) {
                $newValues['admin_id'] = $adminId;
            }

            $sql = "INSERT INTO audit_logs (
                user_id, action, resource_type, resource_id,
                old_values, new_values, ip_address, user_agent, created_at
            ) VALUES (
                :user_id, :action, :resource_type, :resource_id,
                :old_values, :new_values, :ip_address, :user_agent, NOW()
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id'       => $studentId,
                'action'        => 'balance.' . $actionType,
                'resource_type' => 'student_profile',
                'resource_id'   => $orderId,
                'old_values'    => json_encode($oldValues),
                'new_values'    => json_encode($newValues),
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the transaction
            error_log(json_encode([
                'error'      => 'Failed to create balance audit log',
                'student_id' => $studentId,
                'order_id'   => $orderId,
                'exception'  => $e->getMessage(),
                'timestamp'  => date('Y-m-d H:i:s'),
            ]));
        }
    }
}
