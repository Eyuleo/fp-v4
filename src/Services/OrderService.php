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

    public function __construct(OrderRepository $orderRepository, ServiceRepository $serviceRepository, PaymentService $paymentService = null)
    {
        $this->orderRepository   = $orderRepository;
        $this->serviceRepository = $serviceRepository;
        $this->validator         = new OrderValidator();
        $this->emailService      = new EmailService();
        $this->fileService       = new FileService();

        $db                        = $orderRepository->getDb();
        $this->userRepository      = new UserRepository($db);
        $this->messageRepository   = new MessageRepository($db);
        $mailService               = new MailService();
        $notificationRepository    = new NotificationRepository($db);
        $this->notificationService = new NotificationService($mailService, $notificationRepository);

        if ($paymentService === null) {
            $paymentRepository    = new PaymentRepository($db);
            $this->paymentService = new PaymentService($paymentRepository, $db);
        } else {
            $this->paymentService = $paymentService;
        }
    }

    public function createOrder(int $clientId, int $serviceId, array $data): array
    {
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
        $deadline       = date('Y-m-d H:i:s', strtotime('+' . $service['delivery_days'] . ' days'));

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

        $this->orderRepository->beginTransaction();
        try {
            $uploadedFiles = $this->handleDeliveryFileUploads($orderId, $data['files']);
            if (empty($uploadedFiles)) {
                throw new Exception('Failed to upload delivery files');
            }

            $this->orderRepository->update($orderId, [
                'status'           => 'delivered',
                'delivery_message' => $deliveryMessage,
                'delivery_files'   => $uploadedFiles,
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

        $this->orderRepository->beginTransaction();
        try {
            $orderAmount      = (float) $order['price'];
            $commissionRate   = (float) $order['commission_rate'];
            $commissionAmount = $orderAmount * ($commissionRate / 100);
            $studentEarnings  = $orderAmount - $commissionAmount;

            $this->orderRepository->update($orderId, [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);
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
            error_log('Order completion error: ' . $e->getMessage());
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

        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'errors' => ['revision_reason' => 'Please provide a reason for the revision request']];
        }

        $this->orderRepository->beginTransaction();
        try {
            $newRevisionCount = ($order['revision_count'] ?? 0) + 1;
            $this->orderRepository->update($orderId, [
                'status'          => 'revision_requested',
                'revision_count'  => $newRevisionCount,
                'revision_reason' => $reason,
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
     * PATCH: Admin force completion of overdue delivered order.
     * Conditions:
     * - Admin role
     * - Order status = delivered
     * - Current time > deadline
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

        $deadlineTs = strtotime($order['deadline']);
        if (time() <= $deadlineTs) {
            return ['success' => false, 'errors' => ['deadline' => 'Order is not overdue; cannot force completion yet']];
        }

        $this->orderRepository->beginTransaction();
        try {
            $orderAmount      = (float) $order['price'];
            $commissionRate   = (float) $order['commission_rate'];
            $commissionAmount = $orderAmount * ($commissionRate / 100);
            $studentEarnings  = $orderAmount - $commissionAmount;

            $this->orderRepository->update($orderId, [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);
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
            error_log('Admin force completion error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['database' => 'Failed to force complete order. Please try again.']];
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
}
