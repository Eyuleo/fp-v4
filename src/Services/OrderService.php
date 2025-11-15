<?php

require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Repositories/PaymentRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';
require_once __DIR__ . '/../Repositories/MessageRepository.php'; // Added to log revision as a system message
require_once __DIR__ . '/../Validators/OrderValidator.php';
require_once __DIR__ . '/PaymentService.php';
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/FileService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/MailService.php';

/**
 * Order Service
 *
 * Business logic for order management
 */
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
    private MessageRepository $messageRepository; // Added

    public function __construct(OrderRepository $orderRepository, ServiceRepository $serviceRepository, PaymentService $paymentService = null)
    {
        $this->orderRepository   = $orderRepository;
        $this->serviceRepository = $serviceRepository;
        $this->validator         = new OrderValidator();
        $this->emailService      = new EmailService();
        $this->fileService       = new FileService();

        // Initialize repositories and services
        $db                        = $orderRepository->getDb();
        $this->userRepository      = new UserRepository($db);
        $this->messageRepository   = new MessageRepository($db); // Added
        $mailService               = new MailService();
        $notificationRepository    = new NotificationRepository($db);
        $this->notificationService = new NotificationService($mailService, $notificationRepository);

        // Initialize PaymentService if not provided
        if ($paymentService === null) {
            $paymentRepository    = new PaymentRepository($db);
            $this->paymentService = new PaymentService($paymentRepository, $db);
        } else {
            $this->paymentService = $paymentService;
        }
    }

    /**
     * Create a new order
     *
     * @param int $clientId
     * @param int $serviceId
     * @param array $data Order data (requirements, files)
     * @return array ['success' => bool, 'order_id' => int|null, 'order' => array|null, 'errors' => array]
     */
    public function createOrder(int $clientId, int $serviceId, array $data): array
    {
        // Validate input data
        if (! $this->validator->validateCreate($data)) {
            return [
                'success'  => false,
                'order_id' => null,
                'order'    => null,
                'errors'   => $this->validator->getErrors(),
            ];
        }

        // Get service details
        $service = $this->serviceRepository->findById($serviceId);

        if (! $service) {
            return [
                'success'  => false,
                'order_id' => null,
                'order'    => null,
                'errors'   => ['service' => 'Service not found'],
            ];
        }

        // Check service is active
        if ($service['status'] !== 'active') {
            return [
                'success'  => false,
                'order_id' => null,
                'order'    => null,
                'errors'   => ['service' => 'Service is not available'],
            ];
        }

        // Validate files if provided
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

        // Get platform settings
        $commissionRate = $this->orderRepository->getCommissionRate();
        $maxRevisions   = $this->orderRepository->getMaxRevisions();

        // Calculate deadline
        $deadline = date('Y-m-d H:i:s', strtotime('+' . $service['delivery_days'] . ' days'));

        // Prepare order data
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

        // Create order first to get ID
        $orderId = $this->orderRepository->create($orderData);

        // Handle file uploads if provided
        if (! empty($data['files']) && $orderId) {
            $uploadedFiles = $this->handleFileUploads($orderId, $data['files']);

            if (! empty($uploadedFiles)) {
                // Update order with file paths
                $this->orderRepository->update($orderId, [
                    'requirement_files' => $uploadedFiles,
                ]);
            }
        }

        // Get the created order
        $order = $this->orderRepository->findById($orderId);

        // Send notification to student about new order
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

    /**
     * Get order by ID
     *
     * @param int $orderId
     * @return array|null
     */
    public function getOrderById(int $orderId): ?array
    {
        return $this->orderRepository->findById($orderId);
    }

    /**
     * Get orders for a client
     *
     * @param int $clientId
     * @param string|null $status
     * @return array
     */
    public function getOrdersForClient(int $clientId, ?string $status = null): array
    {
        return $this->orderRepository->findByClientId($clientId, $status);
    }

    /**
     * Get orders for a student
     *
     * @param int $studentId
     * @param string|null $status
     * @return array
     */
    public function getOrdersForStudent(int $studentId, ?string $status = null): array
    {
        return $this->orderRepository->findByStudentId($studentId, $status);
    }

    /**
     * Deliver an order (student)
     *
     * @param int $orderId
     * @param int $studentId
     * @param array $data Delivery data (delivery_message, files)
     * @return array ['success' => bool, 'errors' => array]
     */
    public function deliverOrder(int $orderId, int $studentId, array $data): array
    {
        // Get order
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            return [
                'success' => false,
                'errors'  => ['order' => 'Order not found'],
            ];
        }

        // Verify order belongs to student
        if ($order['student_id'] != $studentId) {
            return [
                'success' => false,
                'errors'  => ['authorization' => 'You are not authorized to deliver this order'],
            ];
        }

        // Verify order is in correct status (in_progress or revision_requested)
        if (! in_array($order['status'], ['in_progress', 'revision_requested'])) {
            return [
                'success' => false,
                'errors'  => ['status' => 'Order cannot be delivered in its current status'],
            ];
        }

        // Check if delivery deadline has passed
        $deadlineTs = strtotime($order['deadline'] ?? '');
        if ($deadlineTs !== false && time() > $deadlineTs) {
            return [
                'success' => false,
                'errors'  => ['deadline' => 'The delivery deadline has passed. Please contact support.'],
            ];
        }

        // Validate delivery message
        $deliveryMessage = trim($data['delivery_message'] ?? '');
        if (empty($deliveryMessage)) {
            return [
                'success' => false,
                'errors'  => ['delivery_message' => 'Delivery message is required'],
            ];
        }

        // Validate files are provided
        if (empty($data['files'])) {
            return [
                'success' => false,
                'errors'  => ['files' => 'At least one delivery file is required'],
            ];
        }

        // Validate files
        if (! $this->validator->validateFiles($data['files'])) {
            return [
                'success' => false,
                'errors'  => $this->validator->getErrors(),
            ];
        }

        // Begin transaction
        $this->orderRepository->beginTransaction();

        try {
            // Handle file uploads
            $uploadedFiles = $this->handleDeliveryFileUploads($orderId, $data['files']);

            if (empty($uploadedFiles)) {
                throw new Exception('Failed to upload delivery files');
            }

            // Update order with delivery information
            $this->orderRepository->update($orderId, [
                'status'           => 'delivered',
                'delivery_message' => $deliveryMessage,
                'delivery_files'   => $uploadedFiles,
            ]);

            // Send notification to client about order delivery
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

            // Commit transaction
            $this->orderRepository->commit();

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->orderRepository->rollback();
            error_log('Order delivery error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['database' => 'Failed to deliver order. Please try again.'],
            ];
        }
    }

    /**
     * Complete an order (client)
     *
     * @param int $orderId
     * @param int $clientId
     * @return array ['success' => bool, 'errors' => array]
     */
    public function completeOrder(int $orderId, int $clientId): array
    {
        // Get order
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            return [
                'success' => false,
                'errors'  => ['order' => 'Order not found'],
            ];
        }

        // Verify order belongs to client
        if ($order['client_id'] != $clientId) {
            return [
                'success' => false,
                'errors'  => ['authorization' => 'You are not authorized to complete this order'],
            ];
        }

        // Verify order is in delivered status
        if ($order['status'] !== 'delivered') {
            return [
                'success' => false,
                'errors'  => ['status' => 'Order cannot be completed in its current status'],
            ];
        }

        // Begin transaction
        $this->orderRepository->beginTransaction();

        try {
            // Calculate student earnings
            $orderAmount      = (float) $order['price'];
            $commissionRate   = (float) $order['commission_rate'];
            $commissionAmount = $orderAmount * ($commissionRate / 100);
            $studentEarnings  = $orderAmount - $commissionAmount;

            // Update order status to completed
            $this->orderRepository->update($orderId, [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            // Credit student's available balance
            $this->orderRepository->addToStudentBalance($order['student_id'], $studentEarnings);

            // Update student profile total_orders counter
            $this->orderRepository->incrementStudentOrderCount($order['student_id']);

            // Send notifications to both parties about order completion
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

            // Commit transaction
            $this->orderRepository->commit();

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->orderRepository->rollback();
            error_log('Order completion error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['database' => 'Failed to complete order. Please try again.'],
            ];
        }
    }

    /**
     * Request revision on an order (client)
     *
     * @param int $orderId
     * @param int $clientId
     * @param string $reason
     * @return array ['success' => bool, 'errors' => array]
     */
    public function requestRevision(int $orderId, int $clientId, string $reason): array
    {
        // Get order
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            return [
                'success' => false,
                'errors'  => ['order' => 'Order not found'],
            ];
        }

        // Verify order belongs to client
        if ($order['client_id'] != $clientId) {
            return [
                'success' => false,
                'errors'  => ['authorization' => 'You are not authorized to request revision on this order'],
            ];
        }

        // Verify order is in delivered status
        if ($order['status'] !== 'delivered') {
            return [
                'success' => false,
                'errors'  => ['status' => 'Order must be in delivered status to request revision'],
            ];
        }

        // Check revision limit
        $maxRevisions = $order['max_revisions'] ?? 3;
        if (($order['revision_count'] ?? 0) >= $maxRevisions) {
            return [
                'success' => false,
                'errors'  => ['revision_limit' => 'Maximum number of revisions reached. Please open a dispute if needed.'],
            ];
        }

        // Validate revision reason
        $reason = trim($reason);
        if (empty($reason)) {
            return [
                'success' => false,
                'errors'  => ['revision_reason' => 'Please provide a reason for the revision request'],
            ];
        }

        // Begin transaction
        $this->orderRepository->beginTransaction();

        try {
            // Update order status and increment revision count
            $newRevisionCount = ($order['revision_count'] ?? 0) + 1;

            $this->orderRepository->update($orderId, [
                'status'         => 'revision_requested',
                'revision_count' => $newRevisionCount,
            ]);

            // Log the revision reason as a system message in the thread so both parties can see it
            try {
                $this->messageRepository->create([
                    'order_id'    => $orderId,
                    'sender_id'   => $clientId,
                    'content'     => "Revision requested: " . $reason,
                    'attachments' => [],
                    'is_flagged'  => 0,
                ]);
            } catch (Exception $e) {
                // Non-fatal; continue
                error_log("Failed to create revision system message for order #{$orderId}: " . $e->getMessage());
            }

            // Send notification to student about revision request
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

            // Commit transaction
            $this->orderRepository->commit();

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->orderRepository->rollback();
            error_log('Revision request error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['database' => 'Failed to request revision. Please try again.'],
            ];
        }
    }

    /**
     * Cancel an order (client or student, only in pending status)
     *
     * @param int $orderId
     * @param int $userId
     * @param string $reason
     * @return array ['success' => bool, 'errors' => array]
     */
    public function cancelOrder(int $orderId, int $userId, string $reason): array
    {
        // Get order
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            return [
                'success' => false,
                'errors'  => ['order' => 'Order not found'],
            ];
        }

        // Verify user is authorized (client or student of the order)
        if ($order['client_id'] != $userId && $order['student_id'] != $userId) {
            return [
                'success' => false,
                'errors'  => ['authorization' => 'You are not authorized to cancel this order'],
            ];
        }

        // Verify order is in pending status (only allow cancellation before work starts)
        if ($order['status'] !== 'pending') {
            return [
                'success' => false,
                'errors'  => ['status' => 'Order can only be cancelled while in pending status. Please open a dispute for orders in progress.'],
            ];
        }

        // Validate cancellation reason
        $reason = trim($reason);
        if (empty($reason)) {
            $reason = 'Order cancelled by user';
        }

        // Begin transaction
        $this->orderRepository->beginTransaction();

        try {
            // Update order status to cancelled
            $this->orderRepository->update($orderId, [
                'status'              => 'cancelled',
                'cancelled_at'        => date('Y-m-d H:i:s'),
                'cancellation_reason' => $reason,
            ]);

            // Process full refund
            $refundResult = $this->paymentService->refundPayment($order);

            if (! $refundResult['success']) {
                // Rollback if refund fails
                $this->orderRepository->rollback();
                error_log("Refund failed for order #{$orderId}: " . implode(', ', $refundResult['errors']));

                return [
                    'success' => false,
                    'errors'  => ['refund' => 'Failed to process refund. Please contact support.'],
                ];
            }

            // Send notifications to both parties about order cancellation
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

            // Commit transaction
            $this->orderRepository->commit();

            return [
                'success' => true,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->orderRepository->rollback();
            error_log('Order cancellation error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['database' => 'Failed to cancel order. Please try again.'],
            ];
        }
    }

    /**
     * Handle file uploads for order requirements
     *
     * @param int $orderId
     * @param array $files
     * @return array Array of file metadata
     */
    private function handleFileUploads(int $orderId, array $files): array
    {
        // Return empty array if no files provided
        if (empty($files)) {
            return [];
        }

        // Use FileService to upload multiple files to orders/{orderId}
        $result = $this->fileService->uploadMultiple($files, 'orders/requirements', $orderId);

        if (! $result['success'] && ! empty($result['errors'])) {
            throw new Exception('File upload failed: ' . implode(', ', $result['errors']));
        }

        return $result['files'];
    }

    /**
     * Handle file uploads for order delivery
     *
     * @param int $orderId
     * @param array $files
     * @return array Array of file metadata
     */
    private function handleDeliveryFileUploads(int $orderId, array $files): array
    {
        // Return empty array if no files provided
        if (empty($files)) {
            return [];
        }

        // Use FileService to upload multiple files to orders/{orderId}
        $result = $this->fileService->uploadMultiple($files, 'orders/delivery', $orderId);

        if (! $result['success'] && ! empty($result['errors'])) {
            throw new Exception('File upload failed: ' . implode(', ', $result['errors']));
        }

        return $result['files'];
    }
}
