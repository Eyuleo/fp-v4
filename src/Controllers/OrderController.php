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
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to place an order';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();
        if ($user['role'] !== 'client') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        $serviceId = $_GET['service_id'] ?? null;

        if (! $serviceId) {
            $_SESSION['error'] = 'Service not specified';
            header('Location: /services');
            exit;
        }

        $serviceRepository = new ServiceRepository($this->db);
        $service           = $serviceRepository->findByIdWithStudent((int) $serviceId);

        if (! $service) {
            $_SESSION['error'] = 'Service not found';
            header('Location: /services');
            exit;
        }

        if ($service['status'] !== 'active') {
            $_SESSION['error'] = 'This service is not available';
            header('Location: /services/' . $serviceId);
            exit;
        }

        include __DIR__ . '/../../views/client/orders/create.php';
        clear_old_input();
    }

    /**
     * Store new order and redirect to Stripe checkout
     *
     * POST /orders/store
     */
    public function store(): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to place an order';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();
        if ($user['role'] !== 'client') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /services');
            exit;
        }

        $serviceId    = (int) ($_POST['service_id'] ?? 0);
        $requirements = $_POST['requirements'] ?? '';

        $serviceRepository = new ServiceRepository($this->db);
        $service           = $serviceRepository->findByIdWithStudent($serviceId);

        if (! $service || $service['status'] !== 'active') {
            $_SESSION['error'] = 'Service not found or not available';
            header('Location: /services/search');
            exit;
        }

        if (strlen(trim($requirements)) < 10) {
            $_SESSION['error'] = 'Requirements must be at least 10 characters';
            flash_input(['requirements' => $requirements, 'service_id' => $serviceId]);
            header('Location: /orders/create?service_id=' . $serviceId);
            exit;
        }

        // Temporary file handling
        $uploadedFiles = [];
        if (isset($_FILES['requirement_files']) && is_array($_FILES['requirement_files']['name'])) {
            $fileCount = count($_FILES['requirement_files']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['requirement_files']['error'][$i] === UPLOAD_ERR_OK) {
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

        // Save pending order data (will add payment info after successful session creation)
        $_SESSION['pending_order'] = [
            'client_id'    => $user['id'],
            'service_id'   => $serviceId,
            'requirements' => $requirements,
            'files'        => $uploadedFiles,
            'created_at'   => time(),
        ];

        $successUrl = getenv('APP_URL') . '/orders/payment-success';
        $cancelUrl  = getenv('APP_URL') . '/orders/create?service_id=' . $serviceId;

        $orderData = [
            'id'            => 'pending',
            'service_title' => $service['title'],
            'price'         => $service['price'],
            'client_id'     => $user['id'],
            'student_id'    => $service['student_id'],
        ];

        $paymentResult = $this->paymentService->createCheckoutSession($orderData, $successUrl, $cancelUrl);

        if (! $paymentResult['success']) {
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

        // PATCH: Persist payment identifiers to session so we can finalize without webhook
        $_SESSION['pending_order']['payment_id']        = $paymentResult['payment_id'];
        $_SESSION['pending_order']['stripe_session_id'] = $paymentResult['session_id'];

        header('Location: ' . $paymentResult['session_url']);
        exit;
    }

    /**
     * Handle successful payment and create order
     *
     * GET /orders/payment-success
     */
    public function paymentSuccess(): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        if (! isset($_SESSION['pending_order'])) {
            $_SESSION['error'] = 'No pending order found';
            header('Location: /services/search');
            exit;
        }

        $pendingOrder = $_SESSION['pending_order'];

        if ($pendingOrder['client_id'] != $user['id']) {
            $_SESSION['error'] = 'Invalid order data';
            unset($_SESSION['pending_order']);
            header('Location: /services/search');
            exit;
        }

        // Reconstruct files from temp paths
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

        // Create order now (starts as in_progress per service logic)
        $result = $this->orderService->createOrder(
            $pendingOrder['client_id'],
            $pendingOrder['service_id'],
            [
                'requirements' => $pendingOrder['requirements'],
                'files'        => $files,
            ]
        );

        if (! $result['success']) {
            foreach ($pendingOrder['files'] as $fileData) {
                if (file_exists($fileData['temp_path'])) {
                    unlink($fileData['temp_path']);
                }
            }
            unset($_SESSION['pending_order']);

            $_SESSION['error'] = 'Failed to create order: ' . implode(', ', array_values($result['errors']));
            header('Location: /services/search');
            exit;
        }

        $newOrderId      = (int) $result['order_id'];
        $paymentId       = $pendingOrder['payment_id'] ?? null;
        $stripeSessionId = $pendingOrder['stripe_session_id'] ?? null;

        // PATCH: Finalize payment without webhook (link order + mark succeeded)
        if ($paymentId && $stripeSessionId) {
            try {
                $this->paymentService->finalizePaymentWithoutWebhook(
                    (int) $paymentId,
                    $stripeSessionId,
                    $newOrderId
                );
            } catch (Exception $e) {
                error_log('Failed to finalize payment without webhook: ' . $e->getMessage());
                // Non-fatal: order exists, but payment may appear pending.
            }
        }

        // Clean up temp files
        foreach ($pendingOrder['files'] as $fileData) {
            if (file_exists($fileData['temp_path'])) {
                unlink($fileData['temp_path']);
            }
        }

        unset($_SESSION['pending_order']);

        $_SESSION['success'] = 'Order placed successfully! The student will be notified.';
        header('Location: /orders/' . $newOrderId);
        exit;
    }

    /**
     * Show order details
     *
     * GET /orders/{id}
     */
    public function show(int $id): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to view orders';
            header('Location: /login');
            exit;
        }

        $user  = Auth::user();
        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        if ($order['client_id'] !== $user['id'] &&
            $order['student_id'] !== $user['id'] &&
            $user['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        if (isset($_GET['payment'])) {
            if ($_GET['payment'] === 'success') {
                $_SESSION['success'] = 'Payment successful! Your order has been placed.';
            } elseif ($_GET['payment'] === 'cancelled') {
                $_SESSION['error'] = 'Payment was cancelled. Please try again.';
            }
        }

        $review = null;
        if ($order['status'] === 'completed') {
            require_once __DIR__ . '/../Services/ReviewService.php';
            require_once __DIR__ . '/../Repositories/ReviewRepository.php';

            $reviewRepository = new ReviewRepository($this->db);
            $orderRepository  = new OrderRepository($this->db);
            $reviewService    = new ReviewService($reviewRepository, $orderRepository);

            $review = $reviewService->getReviewByOrderId($id);
        }

        include __DIR__ . '/../../views/orders/show.php';
    }

    /**
     * List orders for current user
     *
     * GET /orders
     */
    public function index(): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to view orders';
            header('Location: /login');
            exit;
        }

        $user   = Auth::user();
        $status = $_GET['status'] ?? null;

        if ($user['role'] === 'client') {
            $orders = $this->orderService->getOrdersForClient($user['id'], $status);
            view('client/orders/index', ['orders' => $orders], 'dashboard');
        } elseif ($user['role'] === 'student') {
            $orders = $this->orderService->getOrdersForStudent($user['id'], $status);
            view('student/orders/index', ['orders' => $orders], 'dashboard');
        } else {
            $orders = []; // Admin list not implemented
            view('admin/orders/index', ['orders' => $orders], 'dashboard');
        }
    }

    /**
     * Deprecated acceptance route
     */
    public function accept(int $id): void
    {
        http_response_code(410);
        $_SESSION['error'] = 'Order acceptance is no longer required. Orders automatically start in progress.';
        header('Location: /orders/' . $id);
        exit;
    }

    public function deliver(int $id): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to deliver orders';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /orders/' . $id);
            exit;
        }

        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        require_once __DIR__ . '/../Policies/OrderPolicy.php';
        $policy = new OrderPolicy();

        if (! $policy->canDeliver($user, $order)) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to deliver this order';
            header('Location: /orders/' . $id);
            exit;
        }

        $deliveryMessage = $_POST['delivery_message'] ?? '';

        $files = [];
        if (isset($_FILES['delivery_files']) && is_array($_FILES['delivery_files']['name'])) {
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

    public function complete(int $id): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to complete orders';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /orders/' . $id);
            exit;
        }

        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        require_once __DIR__ . '/../Policies/OrderPolicy.php';
        $policy = new OrderPolicy();

        if (! $policy->canComplete($user, $order)) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to complete this order';
            header('Location: /orders/' . $id);
            exit;
        }

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

    public function requestRevision(int $id): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to request revisions';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /orders/' . $id);
            exit;
        }

        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        require_once __DIR__ . '/../Policies/OrderPolicy.php';
        $policy = new OrderPolicy();

        if (! $policy->canRequestRevision($user, $order)) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to request revision on this order';
            header('Location: /orders/' . $id);
            exit;
        }

        $revisionReason = $_POST['revision_reason'] ?? '';

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

    public function cancel(int $id): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to cancel orders';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            $_SESSION['error'] = 'Only administrators can cancel orders';
            header('Location: /orders/' . $id);
            exit;
        }

        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /orders/' . $id);
            exit;
        }

        $order = $this->orderService->getOrderById($id);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        require_once __DIR__ . '/../Policies/OrderPolicy.php';
        $policy = new OrderPolicy();

        if (! $policy->canCancel($user, $order)) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to cancel this order';
            header('Location: /orders/' . $id);
            exit;
        }

        $cancellationReason = $_POST['cancellation_reason'] ?? '';

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
