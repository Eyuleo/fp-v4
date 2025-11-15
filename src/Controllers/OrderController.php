<?php

require_once __DIR__ . '/../Services/OrderService.php';
require_once __DIR__ . '/../Services/PaymentService.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/PaymentRepository.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Order Controller
 *
 * Handles order-related HTTP requests
 */
class OrderController
{
    private OrderService $orderService;
    private PaymentService $paymentService;
    private PDO $db;

    public function __construct()
    {
        $this->db = getDatabaseConnection();

        $orderRepository   = new OrderRepository($this->db);
        $serviceRepository = new ServiceRepository($this->db);
        $paymentRepository = new PaymentRepository($this->db);

        $this->paymentService = new PaymentService($paymentRepository, $this->db);
        $this->orderService   = new OrderService($orderRepository, $serviceRepository, $this->paymentService);
    }

    /**
     * Show order creation form
     *
     * GET /orders/create?service_id={id}
     */
    public function create(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to place an order';
            header('Location: /login');
            exit;
        }

        // Check user is a client
        $user = Auth::user();
        if ($user['role'] !== 'client') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Get service ID from query string
        $serviceId = $_GET['service_id'] ?? null;

        if (! $serviceId) {
            $_SESSION['error'] = 'Service not specified';
            header('Location: /services');
            exit;
        }

        // Get service details
        $serviceRepository = new ServiceRepository($this->db);
        $service           = $serviceRepository->findByIdWithStudent((int) $serviceId);

        if (! $service) {
            $_SESSION['error'] = 'Service not found';
            header('Location: /services');
            exit;
        }

        // Check service is active
        if ($service['status'] !== 'active') {
            $_SESSION['error'] = 'This service is not available';
            header('Location: /services/' . $serviceId);
            exit;
        }

        // Render order creation form
        include __DIR__ . '/../../views/client/orders/create.php';

        // Clear old input after rendering
        clear_old_input();
    }

    /**
     * Store new order and redirect to Stripe checkout
     *
     * POST /orders/store
     */
    public function store(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to place an order';
            header('Location: /login');
            exit;
        }

        // Check user is a client
        $user = Auth::user();
        if ($user['role'] !== 'client') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Validate CSRF token
        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /services');
            exit;
        }

        // Get form data
        $serviceId    = (int) ($_POST['service_id'] ?? 0);
        $requirements = $_POST['requirements'] ?? '';

        // Get service details for validation and pricing
        $serviceRepository = new ServiceRepository($this->db);
        $service           = $serviceRepository->findByIdWithStudent($serviceId);

        if (! $service || $service['status'] !== 'active') {
            $_SESSION['error'] = 'Service not found or not available';
            header('Location: /services/search');
            exit;
        }

        // Validate requirements
        if (strlen(trim($requirements)) < 10) {
            $_SESSION['error'] = 'Requirements must be at least 10 characters';
            flash_input(['requirements' => $requirements, 'service_id' => $serviceId]);
            header('Location: /orders/create?service_id=' . $serviceId);
            exit;
        }

        // Handle file uploads temporarily
        $uploadedFiles = [];
        if (isset($_FILES['requirement_files']) && is_array($_FILES['requirement_files']['name'])) {
            $fileCount = count($_FILES['requirement_files']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['requirement_files']['error'][$i] === UPLOAD_ERR_OK) {
                    // Move to temp location
                    $tempDir = __DIR__ . '/../../storage/temp';
                    if (! is_dir($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }

                    $tempFile = $tempDir . '/' . uniqid() . '_' . $_FILES['requirement_files']['name'][$i];
                    if (move_uploaded_file($_FILES['requirement_files']['tmp_name'][$i], $tempFile)) {
                        $uploadedFiles[] = [
                            'temp_path'     => $tempFile,
                            'original_name' => $_FILES['requirement_files']['name'][$i],
                            'size'          => $_FILES['requirement_files']['size'][$i],
                        ];
                    }
                }
            }
        }

        // Store order data in session for after payment
        $_SESSION['pending_order'] = [
            'client_id'    => $user['id'],
            'service_id'   => $serviceId,
            'requirements' => $requirements,
            'files'        => $uploadedFiles,
            'created_at'   => time(),
        ];

        // Create Stripe checkout session with metadata
        $successUrl = getenv('APP_URL') . '/orders/payment-success';
        $cancelUrl  = getenv('APP_URL') . '/orders/create?service_id=' . $serviceId;

        // Prepare order data for checkout
        $orderData = [
            'id'            => 'pending',
            'service_title' => $service['title'],
            'price'         => $service['price'],
            'client_id'     => $user['id'],
            'student_id'    => $service['student_id'],
        ];

        $paymentResult = $this->paymentService->createCheckoutSession($orderData, $successUrl, $cancelUrl);

        if (! $paymentResult['success']) {
            // Clean up temp files
            foreach ($uploadedFiles as $file) {
                if (file_exists($file['temp_path'])) {
                    unlink($file['temp_path']);
                }
            }
            unset($_SESSION['pending_order']);

            $_SESSION['error'] = implode(', ', array_values($paymentResult['errors']));
            header('Location: /orders/create?service_id=' . $serviceId);
            exit;
        }

        // Redirect to Stripe checkout
        header('Location: ' . $paymentResult['session_url']);
        exit;
    }

    /**
     * Show order details
     *
     * GET /orders/{id}
     */
    public function show(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to view orders';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Get order
        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        // Check authorization (client, student, or admin can view)
        if ($order['client_id'] !== $user['id'] &&
            $order['student_id'] !== $user['id'] &&
            $user['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Check for payment status messages
        if (isset($_GET['payment'])) {
            if ($_GET['payment'] === 'success') {
                $_SESSION['success'] = 'Payment successful! Your order has been placed.';
            } elseif ($_GET['payment'] === 'cancelled') {
                $_SESSION['error'] = 'Payment was cancelled. Please try again.';
            }
        }

        // Get review if order is completed
        $review = null;
        if ($order['status'] === 'completed') {
            require_once __DIR__ . '/../Services/ReviewService.php';
            require_once __DIR__ . '/../Repositories/ReviewRepository.php';

            $reviewRepository = new ReviewRepository($this->db);
            $orderRepository  = new OrderRepository($this->db);
            $reviewService    = new ReviewService($reviewRepository, $orderRepository);

            $review = $reviewService->getReviewByOrderId($id);
        }

        // Render order details
        include __DIR__ . '/../../views/orders/show.php';
    }

    /**
     * List orders for current user
     *
     * GET /orders
     */
    public function index(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to view orders';
            header('Location: /login');
            exit;
        }

        $user   = Auth::user();
        $status = $_GET['status'] ?? null;

        // Get orders based on user role
        if ($user['role'] === 'client') {
            $orders = $this->orderService->getOrdersForClient($user['id'], $status);
            view('client/orders/index', ['orders' => $orders], 'dashboard');
        } elseif ($user['role'] === 'student') {
            $orders = $this->orderService->getOrdersForStudent($user['id'], $status);
            view('student/orders/index', ['orders' => $orders], 'dashboard');
        } else {
            // Admin - show all orders (to be implemented)
            $orders = [];
            view('admin/orders/index', ['orders' => $orders], 'dashboard');
        }
    }

    /**
     * Handle successful payment and create order
     *
     * GET /orders/payment-success
     */
    public function paymentSuccess(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Check if we have pending order data
        if (! isset($_SESSION['pending_order'])) {
            $_SESSION['error'] = 'No pending order found';
            header('Location: /services/search');
            exit;
        }

        $pendingOrder = $_SESSION['pending_order'];

        // Verify the order belongs to this user
        if ($pendingOrder['client_id'] != $user['id']) {
            $_SESSION['error'] = 'Invalid order data';
            unset($_SESSION['pending_order']);
            header('Location: /services/search');
            exit;
        }

        // Move temp files to permanent location
        $files = [];
        foreach ($pendingOrder['files'] as $fileData) {
            if (file_exists($fileData['temp_path'])) {
                $files[] = [
                    'name'     => $fileData['original_name'],
                    'tmp_name' => $fileData['temp_path'],
                    'size'     => $fileData['size'],
                    'error'    => UPLOAD_ERR_OK,
                    'type'     => mime_content_type($fileData['temp_path']),
                ];
            }
        }

        // Create the order now that payment is confirmed
        $result = $this->orderService->createOrder(
            $pendingOrder['client_id'],
            $pendingOrder['service_id'],
            [
                'requirements' => $pendingOrder['requirements'],
                'files'        => $files,
            ]
        );

        // Attempt to link the payment record to the newly created order
        if ($result['success'] && isset($pendingOrder['payment_id'])) {
            try {
                $paymentRepository = new PaymentRepository($this->db);
                $paymentRepository->update((int) $pendingOrder['payment_id'], [
                    'order_id' => (int) $result['order_id'],
                ]);
            } catch (Exception $e) {
                error_log('Failed to link payment to order: ' . $e->getMessage());
                // continue; not fatal for user flow
            }
        }

        // Clean up temp files
        foreach ($pendingOrder['files'] as $fileData) {
            if (file_exists($fileData['temp_path'])) {
                unlink($fileData['temp_path']);
            }
        }

        // Clear pending order from session
        unset($_SESSION['pending_order']);

        if (! $result['success']) {
            $_SESSION['error'] = 'Failed to create order: ' . implode(', ', array_values($result['errors']));
            header('Location: /services/search');
            exit;
        }

        $_SESSION['success'] = 'Order placed successfully! The student will be notified.';
        header('Location: /orders/' . $result['order_id']);
        exit;
    }

    /**
     * Accept an order (student only)
     *
     * @deprecated This method is deprecated as orders now automatically start in progress.
     *             Orders no longer require student acceptance.
     *
     * POST /orders/{id}/accept
     */
    public function accept(int $id): void
    {
                                 // This functionality has been removed - orders now automatically start in progress
        http_response_code(410); // Gone
        $_SESSION['error'] = 'Order acceptance is no longer required. Orders automatically start in progress.';
        header('Location: /orders/' . $id);
        exit;
    }

    /**
     * Deliver an order (student only)
     *
     * POST /orders/{id}/deliver
     */
    public function deliver(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to deliver orders';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Validate CSRF token
        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /orders/' . $id);
            exit;
        }

        // Get order
        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        // Check authorization using OrderPolicy
        require_once __DIR__ . '/../Policies/OrderPolicy.php';
        $policy = new OrderPolicy();

        if (! $policy->canDeliver($user, $order)) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to deliver this order';
            header('Location: /orders/' . $id);
            exit;
        }

        // Get form data
        $deliveryMessage = $_POST['delivery_message'] ?? '';

        // Handle file uploads
        $files = [];
        if (isset($_FILES['delivery_files']) && is_array($_FILES['delivery_files']['name'])) {
            // Restructure $_FILES array for multiple files
            $fileCount = count($_FILES['delivery_files']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['delivery_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name'     => $_FILES['delivery_files']['name'][$i],
                        'type'     => $_FILES['delivery_files']['type'][$i],
                        'tmp_name' => $_FILES['delivery_files']['tmp_name'][$i],
                        'error'    => $_FILES['delivery_files']['error'][$i],
                        'size'     => $_FILES['delivery_files']['size'][$i],
                    ];
                }
            }
        }

        // Deliver the order
        $result = $this->orderService->deliverOrder($id, $user['id'], [
            'delivery_message' => $deliveryMessage,
            'files'            => $files,
        ]);

        if (! $result['success']) {
            $_SESSION['error'] = implode(', ', array_values($result['errors']));
            header('Location: /orders/' . $id);
            exit;
        }

        $_SESSION['success'] = 'Order delivered successfully! The client will review your work.';
        header('Location: /orders/' . $id);
        exit;
    }

    /**
     * Complete an order (client only)
     *
     * POST /orders/{id}/complete
     */
    public function complete(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to complete orders';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Validate CSRF token
        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /orders/' . $id);
            exit;
        }

        // Get order
        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        // Check authorization using OrderPolicy
        require_once __DIR__ . '/../Policies/OrderPolicy.php';
        $policy = new OrderPolicy();

        if (! $policy->canComplete($user, $order)) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to complete this order';
            header('Location: /orders/' . $id);
            exit;
        }

        // Complete the order
        $result = $this->orderService->completeOrder($id, $user['id']);

        if (! $result['success']) {
            $_SESSION['error'] = implode(', ', array_values($result['errors']));
            header('Location: /orders/' . $id);
            exit;
        }

        $_SESSION['success'] = 'Order completed successfully! Funds have been credited to the student\'s balance.';
        header('Location: /orders/' . $id);
        exit;
    }

    /**
     * Request revision on an order (client only)
     *
     * POST /orders/{id}/request-revision
     */
    public function requestRevision(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to request revisions';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Validate CSRF token
        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /orders/' . $id);
            exit;
        }

        // Get order
        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        // Check authorization using OrderPolicy
        require_once __DIR__ . '/../Policies/OrderPolicy.php';
        $policy = new OrderPolicy();

        if (! $policy->canRequestRevision($user, $order)) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to request revision on this order';
            header('Location: /orders/' . $id);
            exit;
        }

        // Get revision reason
        $revisionReason = $_POST['revision_reason'] ?? '';

        // Request revision
        $result = $this->orderService->requestRevision($id, $user['id'], $revisionReason);

        if (! $result['success']) {
            $_SESSION['error'] = implode(', ', array_values($result['errors']));
            header('Location: /orders/' . $id);
            exit;
        }

        $_SESSION['success'] = 'Revision requested successfully! The student will be notified.';
        header('Location: /orders/' . $id);
        exit;
    }

    /**
     * Cancel an order (admin only)
     *
     * POST /orders/{id}/cancel
     */
    public function cancel(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to cancel orders';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Check admin role - only admins can cancel orders
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            $_SESSION['error'] = 'Only administrators can cancel orders';
            header('Location: /orders/' . $id);
            exit;
        }

        // Validate CSRF token
        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /orders/' . $id);
            exit;
        }

        // Get order
        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        // Check authorization using OrderPolicy
        require_once __DIR__ . '/../Policies/OrderPolicy.php';
        $policy = new OrderPolicy();

        if (! $policy->canCancel($user, $order)) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to cancel this order';
            header('Location: /orders/' . $id);
            exit;
        }

        // Get cancellation reason
        $cancellationReason = $_POST['cancellation_reason'] ?? '';

        // Cancel the order (pass full user array for role checking)
        $result = $this->orderService->cancelOrder($id, $user, $cancellationReason);

        if (! $result['success']) {
            $_SESSION['error'] = implode(', ', array_values($result['errors']));
            header('Location: /orders/' . $id);
            exit;
        }

        $_SESSION['success'] = 'Order cancelled successfully. A full refund has been processed.';
        header('Location: /orders/' . $id);
        exit;
    }
}
