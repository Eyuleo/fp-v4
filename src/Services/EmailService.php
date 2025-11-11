<?php

/**
 * Email Service
 *
 * Handles sending emails for notifications
 */
class EmailService
{
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@marketplace.local';
        $this->fromName  = getenv('MAIL_FROM_NAME') ?: 'Student Skills Marketplace';
    }

    /**
     * Send order placed notification to student
     *
     * @param array $order Order data
     * @param array $student Student data
     * @return bool
     */
    public function sendOrderPlacedNotification(array $order, array $student): bool
    {
        $subject = 'New Order Received - Order #' . $order['id'];

        $message = "Hello {$student['name']},\n\n";
        $message .= "You have received a new order!\n\n";
        $message .= "Order Details:\n";
        $message .= "- Order ID: #{$order['id']}\n";
        $message .= "- Service: {$order['service_title']}\n";
        $message .= "- Price: $" . number_format($order['price'], 2) . "\n";
        $message .= "- Deadline: " . date('M d, Y', strtotime($order['deadline'])) . "\n\n";
        $message .= "Please log in to your dashboard to review and accept this order.\n\n";
        $message .= "View Order: " . getenv('APP_URL') . "/orders/{$order['id']}\n\n";
        $message .= "Best regards,\n";
        $message .= "Student Skills Marketplace Team";

        return $this->send($student['email'], $subject, $message);
    }

    /**
     * Send order accepted notification to client
     *
     * @param array $order Order data
     * @param array $client Client data
     * @return bool
     */
    public function sendOrderAcceptedNotification(array $order, array $client): bool
    {
        $subject = 'Order Accepted - Order #' . $order['id'];

        $message = "Hello {$client['name']},\n\n";
        $message .= "Great news! Your order has been accepted by the student.\n\n";
        $message .= "Order Details:\n";
        $message .= "- Order ID: #{$order['id']}\n";
        $message .= "- Service: {$order['service_title']}\n";
        $message .= "- Expected Delivery: " . date('M d, Y', strtotime($order['deadline'])) . "\n\n";
        $message .= "The student is now working on your order.\n\n";
        $message .= "View Order: " . getenv('APP_URL') . "/orders/{$order['id']}\n\n";
        $message .= "Best regards,\n";
        $message .= "Student Skills Marketplace Team";

        return $this->send($client['email'], $subject, $message);
    }

    /**
     * Send order delivered notification to client
     *
     * @param array $order Order data
     * @param array $client Client data
     * @return bool
     */
    public function sendOrderDeliveredNotification(array $order, array $client): bool
    {
        $subject = 'Order Delivered - Order #' . $order['id'];

        $message = "Hello {$client['name']},\n\n";
        $message .= "Your order has been delivered!\n\n";
        $message .= "Order Details:\n";
        $message .= "- Order ID: #{$order['id']}\n";
        $message .= "- Service: {$order['service_title']}\n\n";
        $message .= "Please review the delivered work and either:\n";
        $message .= "- Accept and complete the order\n";
        $message .= "- Request a revision (if needed)\n\n";
        $message .= "View Order: " . getenv('APP_URL') . "/orders/{$order['id']}\n\n";
        $message .= "Best regards,\n";
        $message .= "Student Skills Marketplace Team";

        return $this->send($client['email'], $subject, $message);
    }

    /**
     * Send order completed notification to student
     *
     * @param array $order Order data
     * @param array $student Student data
     * @param float $earnings Student earnings
     * @return bool
     */
    public function sendOrderCompletedNotification(array $order, array $student, float $earnings): bool
    {
        $subject = 'Order Completed - Order #' . $order['id'];

        $message = "Hello {$student['name']},\n\n";
        $message .= "Congratulations! Your order has been completed.\n\n";
        $message .= "Order Details:\n";
        $message .= "- Order ID: #{$order['id']}\n";
        $message .= "- Service: {$order['service_title']}\n";
        $message .= "- Earnings: $" . number_format($earnings, 2) . "\n\n";
        $message .= "The funds have been added to your available balance.\n\n";
        $message .= "View Order: " . getenv('APP_URL') . "/orders/{$order['id']}\n\n";
        $message .= "Best regards,\n";
        $message .= "Student Skills Marketplace Team";

        return $this->send($student['email'], $subject, $message);
    }

    /**
     * Send revision requested notification to student
     *
     * @param array $order Order data
     * @param array $student Student data
     * @return bool
     */
    public function sendRevisionRequestedNotification(array $order, array $student): bool
    {
        $subject = 'Revision Requested - Order #' . $order['id'];

        $message = "Hello {$student['name']},\n\n";
        $message .= "The client has requested a revision for order #{$order['id']}.\n\n";
        $message .= "Order Details:\n";
        $message .= "- Order ID: #{$order['id']}\n";
        $message .= "- Service: {$order['service_title']}\n";
        $message .= "- Revisions Used: {$order['revision_count']} of {$order['max_revisions']}\n\n";
        $message .= "Please review the revision request and submit updated work.\n\n";
        $message .= "View Order: " . getenv('APP_URL') . "/orders/{$order['id']}\n\n";
        $message .= "Best regards,\n";
        $message .= "Student Skills Marketplace Team";

        return $this->send($student['email'], $subject, $message);
    }

    /**
     * Send order cancelled notification
     *
     * @param array $order Order data
     * @param array $recipient Recipient data
     * @return bool
     */
    public function sendOrderCancelledNotification(array $order, array $recipient): bool
    {
        $subject = 'Order Cancelled - Order #' . $order['id'];

        $message = "Hello {$recipient['name']},\n\n";
        $message .= "Order #{$order['id']} has been cancelled.\n\n";
        $message .= "Order Details:\n";
        $message .= "- Order ID: #{$order['id']}\n";
        $message .= "- Service: {$order['service_title']}\n\n";
        $message .= "A full refund has been processed.\n\n";
        $message .= "Best regards,\n";
        $message .= "Student Skills Marketplace Team";

        return $this->send($recipient['email'], $subject, $message);
    }

    /**
     * Send message received notification
     *
     * @param array $order Order data
     * @param array $recipient Recipient data
     * @param string $messagePreview Preview of the message content
     * @return bool
     */
    public function sendMessageReceivedNotification(array $order, array $recipient, string $messagePreview): bool
    {
        $subject = 'New Message - Order #' . $order['id'];

        // Truncate message preview to 100 characters
        $preview = strlen($messagePreview) > 100 ? substr($messagePreview, 0, 100) . '...' : $messagePreview;

        $message = "Hello {$recipient['name']},\n\n";
        $message .= "You have received a new message for order #{$order['id']}.\n\n";
        $message .= "Message Preview:\n";
        $message .= "{$preview}\n\n";
        $message .= "View Full Message: " . getenv('APP_URL') . "/messages/thread/{$order['id']}\n\n";
        $message .= "Best regards,\n";
        $message .= "Student Skills Marketplace Team";

        return $this->send($recipient['email'], $subject, $message);
    }

    /**
     * Send review submitted notification to student
     *
     * @param array $order Order data
     * @param array $student Student data with rating and comment
     * @return bool
     */
    public function sendReviewSubmittedNotification(array $order, array $student): bool
    {
        $subject = 'New Review Received - Order #' . $order['id'];

        $stars = str_repeat('★', $student['rating']) . str_repeat('☆', 5 - $student['rating']);

        $message = "Hello {$student['name']},\n\n";
        $message .= "You have received a new review!\n\n";
        $message .= "Order Details:\n";
        $message .= "- Order ID: #{$order['id']}\n";
        $message .= "- Service: {$order['service_title']}\n";
        $message .= "- Rating: {$stars} ({$student['rating']}/5)\n\n";

        if (! empty($student['comment'])) {
            $message .= "Review Comment:\n";
            $message .= "{$student['comment']}\n\n";
        }

        $message .= "You can reply to this review from your profile page.\n\n";
        $message .= "View Your Profile: " . getenv('APP_URL') . "/student/profile\n\n";
        $message .= "Best regards,\n";
        $message .= "Student Skills Marketplace Team";

        return $this->send($student['email'], $subject, $message);
    }

    /**
     * Send review reply notification to client
     *
     * @param array $review Review data
     * @param array $client Client data with reply
     * @return bool
     */
    public function sendReviewReplyNotification(array $review, array $client): bool
    {
        $subject = 'Student Replied to Your Review';

        $message = "Hello {$client['name']},\n\n";
        $message .= "The student has replied to your review.\n\n";
        $message .= "Service: {$review['service_title']}\n";
        $message .= "Student: {$review['student_name']}\n\n";
        $message .= "Reply:\n";
        $message .= "{$client['reply']}\n\n";
        $message .= "View Student Profile: " . getenv('APP_URL') . "/student/profile/{$review['student_id']}\n\n";
        $message .= "Best regards,\n";
        $message .= "Student Skills Marketplace Team";

        return $this->send($client['email'], $subject, $message);
    }

    /**
     * Send email using PHP mail function
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message
     * @return bool
     */
    private function send(string $to, string $subject, string $message): bool
    {
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $success = mail($to, $subject, $message, implode("\r\n", $headers));

        if (! $success) {
            error_log("Failed to send email to {$to}: {$subject}");
        }

        return $success;
    }
}
