<?php

/**
 * Notification Controller
 *
 * Handles notification-related HTTP requests
 */
class NotificationController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display notification center
     */
    public function index(): void
    {
        // Ensure user is authenticated
        if (! isset($_SESSION['user_id'])) {
            header('Location: /auth/login');
            exit;
        }

        $userId = $_SESSION['user_id'];

        // Get all notifications for the user
        $notifications = $this->notificationService->getNotifications($userId);
        $unreadCount   = $this->notificationService->getUnreadCount($userId);

        // Render notification center view
        view('notifications/index', compact('notifications', 'unreadCount'), 'dashboard');

    }

    /**
     * Mark notification as read (AJAX endpoint)
     */
    public function markAsRead(): void
    {
        header('Content-Type: application/json');

        // Ensure user is authenticated
        if (! isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        // Get notification ID from request
        $notificationId = $_POST['notification_id'] ?? null;

        if (! $notificationId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            exit;
        }

        try {
            // Mark as read
            $success = $this->notificationService->markAsRead((int) $notificationId, $userId);

            if ($success) {
                // Get updated unread count
                $unreadCount = $this->notificationService->getUnreadCount($userId);

                echo json_encode([
                    'success'      => true,
                    'unread_count' => $unreadCount,
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Notification not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): void
    {
        header('Content-Type: application/json');

        // Ensure user is authenticated
        if (! isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        try {
            $success = $this->notificationService->markAllAsRead($userId);

            if ($success) {
                echo json_encode([
                    'success'      => true,
                    'unread_count' => 0,
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to mark all as read']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Get unread count (AJAX endpoint)
     */
    public function getUnreadCount(): void
    {
        header('Content-Type: application/json');

        // Ensure user is authenticated
        if (! isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        try {
            $unreadCount = $this->notificationService->getUnreadCount($userId);

            echo json_encode([
                'success'      => true,
                'unread_count' => $unreadCount,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }
}
