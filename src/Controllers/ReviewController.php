<?php

require_once __DIR__ . '/../Services/ReviewService.php';
require_once __DIR__ . '/../Repositories/ReviewRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Review Controller
 *
 * Handles review-related HTTP requests
 */
class ReviewController
{
    private ReviewService $reviewService;
    private PDO $db;

    public function __construct()
    {
        $this->db = getDatabaseConnection();

        $reviewRepository = new ReviewRepository($this->db);
        $orderRepository  = new OrderRepository($this->db);

        $this->reviewService = new ReviewService($reviewRepository, $orderRepository);
    }

    /**
     * Show review creation form
     *
     * GET /reviews/create?order_id={id}
     */
    public function create(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to submit a review';
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

        // Get order ID from query string
        $orderId = $_GET['order_id'] ?? null;

        if (! $orderId) {
            $_SESSION['error'] = 'Order not specified';
            header('Location: /orders');
            exit;
        }

        // Get order details
        $orderRepository = new OrderRepository($this->db);
        $order           = $orderRepository->findById((int) $orderId);

        if (! $order) {
            $_SESSION['error'] = 'Order not found';
            header('Location: /orders');
            exit;
        }

        // Check order belongs to client
        if ($order['client_id'] !== $user['id']) {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Check order is completed
        if ($order['status'] !== 'completed') {
            $_SESSION['error'] = 'Only completed orders can be reviewed';
            header('Location: /orders/' . $orderId);
            exit;
        }

        // Check if review already exists
        $existingReview = $this->reviewService->getReviewByOrderId((int) $orderId);
        if ($existingReview) {
            $_SESSION['error'] = 'You have already reviewed this order';
            header('Location: /orders/' . $orderId);
            exit;
        }

        // Render review creation form
        include __DIR__ . '/../../views/reviews/create.php';

        // Clear old input after rendering
        clear_old_input();
    }

    /**
     * Store new review
     *
     * POST /reviews/store
     */
    public function store(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to submit a review';
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
            header('Location: /orders');
            exit;
        }

        // Get form data
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = $_POST['comment'] ?? null;

        // Create review
        $result = $this->reviewService->createReview($orderId, $user['id'], $rating, $comment);

        if (! $result['success']) {
            $_SESSION['error'] = implode(', ', array_values($result['errors']));
            flash_input(['rating' => $rating, 'comment' => $comment, 'order_id' => $orderId]);
            header('Location: /reviews/create?order_id=' . $orderId);
            exit;
        }

        $_SESSION['success'] = 'Review submitted successfully! Thank you for your feedback.';
        header('Location: /orders/' . $orderId);
        exit;
    }

    /**
     * Show review edit form
     *
     * GET /reviews/{id}/edit
     */
    public function edit(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to edit reviews';
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

        // Get review
        $review = $this->reviewService->getReviewById($id);

        if (! $review) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        // Check review belongs to client
        if ($review['client_id'] !== $user['id']) {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Check edit window hasn't expired
        $now          = time();
        $canEditUntil = strtotime($review['can_edit_until']);

        if ($now > $canEditUntil) {
            $_SESSION['error'] = 'The 24-hour edit window has expired';
            header('Location: /orders/' . $review['order_id']);
            exit;
        }

        // Render review edit form
        include __DIR__ . '/../../views/reviews/edit.php';
    }

    /**
     * Update review
     *
     * POST /reviews/{id}/update
     */
    public function update(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to edit reviews';
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
            header('Location: /reviews/' . $id . '/edit');
            exit;
        }

        // Get form data
        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = $_POST['comment'] ?? null;

        // Update review
        $result = $this->reviewService->updateReview($id, $user['id'], $rating, $comment);

        if (! $result['success']) {
            $_SESSION['error'] = implode(', ', array_values($result['errors']));
            header('Location: /reviews/' . $id . '/edit');
            exit;
        }

        $_SESSION['success'] = 'Review updated successfully!';
        header('Location: /orders/' . $result['review']['order_id']);
        exit;
    }

    /**
     * Add student reply to review
     *
     * POST /reviews/{id}/reply
     */
    public function reply(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to reply to reviews';
            header('Location: /login');
            exit;
        }

        // Check user is a student
        $user = Auth::user();
        if ($user['role'] !== 'student') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Validate CSRF token
        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /');
            exit;
        }

        // Get form data
        $reply = $_POST['student_reply'] ?? '';

        // Add reply
        $result = $this->reviewService->addStudentReply($id, $user['id'], $reply);

        if (! $result['success']) {
            $_SESSION['error'] = implode(', ', array_values($result['errors']));

            // Redirect back to where they came from
            if (isset($_SERVER['HTTP_REFERER'])) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header('Location: /student/profile');
            }
            exit;
        }

        $_SESSION['success'] = 'Reply added successfully!';

        // Redirect back to where they came from
        if (isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('Location: /student/profile');
        }
        exit;
    }
}
