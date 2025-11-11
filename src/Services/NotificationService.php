<?php

/**
 * Notification Service
 *
 * Handles email and in-app notifications
 */
class NotificationService
{
    private MailService $mailService;
    private NotificationRepository $notificationRepository;
    private ?string $logFile;

    public function __construct(
        MailService $mailService,
        NotificationRepository $notificationRepository
    ) {
        $this->mailService            = $mailService;
        $this->notificationRepository = $notificationRepository;
        $this->logFile                = __DIR__ . '/../../logs/notifications.log';
    }

    /**
     * Send email notification with retry logic
     */
    public function sendEmail(string $to, string $subject, string $template, array $data): bool
    {
        $maxAttempts = 2;
        $attempt     = 0;
        $lastError   = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $this->mailService->send($to, $subject, $template, $data);

                $this->log('info', "Email sent successfully to {$to}", [
                    'template' => $template,
                    'attempt'  => $attempt,
                ]);

                return true;
            } catch (Exception $e) {
                $lastError = $e->getMessage();

                $this->log('error', "Email send failed (attempt {$attempt}/{$maxAttempts})", [
                    'to'       => $to,
                    'template' => $template,
                    'error'    => $lastError,
                ]);

                // Wait 5 seconds before retry
                if ($attempt < $maxAttempts) {
                    sleep(5);
                }
            }
        }

        // All attempts failed
        $this->log('critical', "Email send failed after {$maxAttempts} attempts", [
            'to'         => $to,
            'template'   => $template,
            'last_error' => $lastError,
        ]);

        return false;
    }

    /**
     * Create in-app notification
     */
    public function createInAppNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): int {
        try {
            $notificationId = $this->notificationRepository->create([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'link'    => $link,
                'is_read' => false,
            ]);

            $this->log('info', "In-app notification created", [
                'notification_id' => $notificationId,
                'user_id'         => $userId,
                'type'            => $type,
            ]);

            return $notificationId;
        } catch (Exception $e) {
            $this->log('error', "Failed to create in-app notification", [
                'user_id' => $userId,
                'type'    => $type,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send both email and in-app notification
     */
    public function notify(
        int $userId,
        string $email,
        string $type,
        string $title,
        string $message,
        string $emailTemplate,
        array $emailData,
        ?string $link = null
    ): void {
        // Create in-app notification
        $this->createInAppNotification($userId, $type, $title, $message, $link);

        // Send email notification
        $this->sendEmail($email, $title, $emailTemplate, $emailData);
    }

    /**
     * Get notifications for a user
     */
    public function getNotifications(int $userId, bool $unreadOnly = false): array
    {
        return $this->notificationRepository->findByUserId($userId, $unreadOnly);
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->getUnreadCount($userId);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        return $this->notificationRepository->markAsRead($notificationId, $userId);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId): bool
    {
        return $this->notificationRepository->markAllAsRead($userId);
    }

    /**
     * Send order placed notification to student
     */
    public function notifyOrderPlaced(array $order, array $student, array $client, array $service): void
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';

        $this->notify(
            $student['id'],
            $student['email'],
            'order_placed',
            'New Order Received',
            "You have received a new order for {$service['title']}",
            'emails/order-placed',
            [
                'student_name'  => $student['name'] ?? $student['email'],
                'client_name'   => $client['name'] ?? $client['email'],
                'service_title' => $service['title'],
                'order_id'      => $order['id'],
                'price'         => $order['price'],
                'deadline'      => date('M j, Y g:i A', strtotime($order['deadline'])),
                'requirements'  => $order['requirements'],
                'order_url'     => $appUrl . '/orders/' . $order['id'],
            ],
            $appUrl . '/orders/' . $order['id']
        );
    }

    /**
     * Send order delivered notification to client
     */
    public function notifyOrderDelivered(array $order, array $client, array $student, array $service): void
    {
        $appUrl      = getenv('APP_URL') ?: 'http://localhost:8000';
        $studentName = $student['name'] ?? $student['email'];

        $this->notify(
            $client['id'],
            $client['email'],
            'order_delivered',
            'Order Delivered',
            "{$studentName} has delivered your order",
            'emails/order-delivered',
            [
                'client_name'      => $client['name'] ?? $client['email'],
                'student_name'     => $student['name'] ?? $student['email'],
                'service_title'    => $service['title'],
                'order_id'         => $order['id'],
                'delivered_at'     => date('M j, Y g:i A'),
                'delivery_message' => $order['delivery_message'] ?? '',
                'order_url'        => $appUrl . '/orders/' . $order['id'],
            ],
            $appUrl . '/orders/' . $order['id']
        );
    }

    /**
     * Send revision requested notification to student
     */
    public function notifyRevisionRequested(array $order, array $student, array $service, string $revisionReason): void
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';

        $this->notify(
            $student['id'],
            $student['email'],
            'revision_requested',
            'Revision Requested',
            "The client has requested revisions for order #{$order['id']}",
            'emails/revision-requested',
            [
                'student_name'    => $student['name'] ?? $student['email'],
                'service_title'   => $service['title'],
                'order_id'        => $order['id'],
                'revision_count'  => $order['revision_count'],
                'max_revisions'   => $order['max_revisions'],
                'revision_reason' => $revisionReason,
                'order_url'       => $appUrl . '/orders/' . $order['id'],
            ],
            $appUrl . '/orders/' . $order['id']
        );
    }

    /**
     * Send order completed notification to both parties
     */
    public function notifyOrderCompleted(array $order, array $client, array $student, array $service, float $studentEarnings): void
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';

        // Notify client
        $this->notify(
            $client['id'],
            $client['email'],
            'order_completed',
            'Order Completed',
            "Your order has been completed successfully",
            'emails/order-completed',
            [
                'recipient_name' => $client['name'] ?? $client['email'],
                'service_title'  => $service['title'],
                'order_id'       => $order['id'],
                'completed_at'   => date('M j, Y g:i A'),
                'is_client'      => true,
                'review_url'     => $appUrl . '/reviews/create?order_id=' . $order['id'],
                'order_url'      => $appUrl . '/orders/' . $order['id'],
            ],
            $appUrl . '/orders/' . $order['id']
        );

        // Notify student
        $this->notify(
            $student['id'],
            $student['email'],
            'order_completed',
            'Order Completed - Payment Received',
            "Your order has been completed and payment has been processed",
            'emails/order-completed',
            [
                'recipient_name' => $student['name'] ?? $student['email'],
                'service_title'  => $service['title'],
                'order_id'       => $order['id'],
                'completed_at'   => date('M j, Y g:i A'),
                'is_client'      => false,
                'earnings'       => $studentEarnings,
                'order_url'      => $appUrl . '/orders/' . $order['id'],
            ],
            $appUrl . '/orders/' . $order['id']
        );
    }

    /**
     * Send review submitted notification to student
     */
    public function notifyReviewSubmitted(array $review, array $student, array $client, array $service, int $orderId): void
    {
        $appUrl     = getenv('APP_URL') ?: 'http://localhost:8000';
        $clientName = $client['name'] ?? $client['email'];

        $this->notify(
            $student['id'],
            $student['email'],
            'review_submitted',
            'New Review Received',
            "{$clientName} has left you a review",
            'emails/review-submitted',
            [
                'student_name'  => $student['name'] ?? $student['email'],
                'client_name'   => $client['name'] ?? $client['email'],
                'service_title' => $service['title'],
                'order_id'      => $orderId,
                'rating'        => $review['rating'],
                'comment'       => $review['comment'] ?? '',
                'review_url'    => $appUrl . '/student/profile#reviews',
            ],
            $appUrl . '/student/profile#reviews'
        );
    }

    /**
     * Send message received notification
     */
    public function notifyMessageReceived(array $message, array $recipient, array $sender, array $order, array $service): void
    {
        $appUrl     = getenv('APP_URL') ?: 'http://localhost:8000';
        $senderName = $sender['name'] ?? $sender['email'];

        $this->notify(
            $recipient['id'],
            $recipient['email'],
            'message_received',
            'New Message Received',
            "{$senderName} sent you a message",
            'emails/message-received',
            [
                'recipient_name'  => $recipient['name'] ?? $recipient['email'],
                'sender_name'     => $sender['name'] ?? $sender['email'],
                'order_id'        => $order['id'],
                'service_title'   => $service['title'],
                'message_content' => $message['content'],
                'has_attachments' => ! empty($message['attachments']),
                'message_url'     => $appUrl . '/messages/' . $order['id'],
            ],
            $appUrl . '/messages/' . $order['id']
        );
    }

    /**
     * Log notification events
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $timestamp   = date('Y-m-d H:i:s');
        $contextJson = ! empty($context) ? json_encode($context) : '';

        $logMessage = sprintf(
            "[%s] [%s] %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            $contextJson
        );

        error_log($logMessage, 3, $this->logFile);
    }
}
