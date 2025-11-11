<?php

require_once __DIR__ . '/../Services/DisputeService.php';
require_once __DIR__ . '/../Repositories/DisputeRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Services/NotificationService.php';
require_once __DIR__ . '/../Services/MailService.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Dispute Controller
 *
 * Handles dispute-related HTTP requests
 */
class DisputeController
{
    private PDO $db;
    private DisputeService $disputeService;
    private OrderRepository $orderRepository;

    public function __construct()
    {
        $this->db = getDatabaseConnection();

        // Initialize repositories
        $disputeRepository     = new DisputeRepository($this->db);
        $this->orderRepository = new OrderRepository($this->db);

        // Initialize notification service
        $mailService            = new MailService();
        $notificationRepository = new NotificationRepository($this->db);
        $notificationService    = new NotificationService($mailService, $notificationRepository);

        // Initialize dispute service
        $this->disputeService = new DisputeService(
            $disputeRepository,
            $this->orderRepository,
            $notificationService,
            $this->db
        );
    }

    /**
     * Show dispute creation form
     *
     * GET /disputes/create?order_id={id}
     */
    public function create(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to access this page';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Get order ID from query string
        $orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

        if (! $orderId) {
            $_SESSION['error'] = 'Order ID is required';
            header('Location: /');
            exit;
        }

        // Get order details
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            $_SESSION['error'] = 'Order not found';
            header('Location: /');
            exit;
        }

        // Verify user is authorized (client or student of the order)
        if ($order['client_id'] != $user['id'] && $order['student_id'] != $user['id']) {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Render view
        include __DIR__ . '/../../views/disputes/create.php';
    }

    /**
     * Store a new dispute
     *
     * POST /disputes/store
     */
    public function store(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to access this page';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Get form data
        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $reason  = $_POST['reason'] ?? '';

        // Create dispute
        $result = $this->disputeService->createDispute($orderId, $user['id'], $reason);

        if (! $result['success']) {
            $_SESSION['error']       = 'Failed to create dispute';
            $_SESSION['form_errors'] = $result['errors'];
            $_SESSION['form_data']   = $_POST;
            header('Location: /disputes/create?order_id=' . $orderId);
            exit;
        }

        $_SESSION['success'] = 'Dispute created successfully. An administrator will review your case.';
        header('Location: /orders/' . $orderId);
        exit;
    }
}
