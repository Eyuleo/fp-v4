<?php

require_once __DIR__ . '/../Repositories/ReviewRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/MailService.php';

/**
 * Review Service
 *
 * Business logic for review management
 */
class ReviewService
{
    private ReviewRepository $reviewRepository;
    private OrderRepository $orderRepository;
    private UserRepository $userRepository;
    private ServiceRepository $serviceRepository;
    private EmailService $emailService;
    private NotificationService $notificationService;
    private PDO $db;

    public function __construct(ReviewRepository $reviewRepository, OrderRepository $orderRepository)
    {
        $this->reviewRepository    = $reviewRepository;
        $this->orderRepository     = $orderRepository;
        $this->emailService        = new EmailService();
        $this->db                  = $orderRepository->getDb();
        $this->userRepository      = new UserRepository($this->db);
        $this->serviceRepository   = new ServiceRepository($this->db);
        $mailService               = new MailService();
        $notificationRepository    = new NotificationRepository($this->db);
        $this->notificationService = new NotificationService($mailService, $notificationRepository);
    }

    /**
     * Create a new review
     *
     * @param int $orderId
     * @param int $clientId
     * @param int $rating
     * @param string|null $comment
     * @return array ['success' => bool, 'review_id' => int|null, 'review' => array|null, 'errors' => array]
     */
    public function createReview(int $orderId, int $clientId, int $rating, ?string $comment): array
    {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            return [
                'success'   => false,
                'review_id' => null,
                'review'    => null,
                'errors'    => ['rating' => 'Rating must be between 1 and 5 stars'],
            ];
        }

        // Get order
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            return [
                'success'   => false,
                'review_id' => null,
                'review'    => null,
                'errors'    => ['order' => 'Order not found'],
            ];
        }

        // Check order is completed
        if ($order['status'] !== 'completed') {
            return [
                'success'   => false,
                'review_id' => null,
                'review'    => null,
                'errors'    => ['order' => 'Only completed orders can be reviewed'],
            ];
        }

        // Check order belongs to client
        if ($order['client_id'] != $clientId) {
            return [
                'success'   => false,
                'review_id' => null,
                'review'    => null,
                'errors'    => ['authorization' => 'You are not authorized to review this order'],
            ];
        }

        // Check no existing review for this order
        $existingReview = $this->reviewRepository->findByOrderId($orderId);

        if ($existingReview) {
            return [
                'success'   => false,
                'review_id' => null,
                'review'    => null,
                'errors'    => ['duplicate' => 'You have already reviewed this order'],
            ];
        }

        // Begin transaction
        $this->reviewRepository->beginTransaction();

        try {
            

            // Create review
            $reviewData = [
                'order_id'       => $orderId,
                'client_id'      => $clientId,
                'student_id'     => $order['student_id'],
                'rating'         => $rating,
                'comment'        => $comment ? trim($comment) : null,
            ];

            $reviewId = $this->reviewRepository->create($reviewData);

            // Update student average_rating and total_reviews
            $this->reviewRepository->updateStudentRating($order['student_id']);

            // Get the created review
            $review = $this->reviewRepository->findById($reviewId);

            // Send notification to student about new review
            try {
                $student = $this->userRepository->findById($order['student_id']);
                $client  = $this->userRepository->findById($order['client_id']);
                $service = $this->serviceRepository->findById($order['service_id']);

                if ($student && $client && $service) {
                    $this->notificationService->notifyReviewSubmitted($review, $student, $client, $service, $orderId);
                }
            } catch (Exception $e) {
                error_log('Failed to send review submitted notification: ' . $e->getMessage());
            }

            // Commit transaction
            $this->reviewRepository->commit();

            return [
                'success'   => true,
                'review_id' => $reviewId,
                'review'    => $review,
                'errors'    => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->reviewRepository->rollback();
            error_log('Review creation error: ' . $e->getMessage());

            return [
                'success'   => false,
                'review_id' => null,
                'review'    => null,
                'errors'    => ['database' => 'Failed to create review. Please try again.'],
            ];
        }
    }

    /**
     * Update a review (DEPRECATED - clients can no longer edit reviews)
     *
     * @deprecated This method is deprecated and will always return an error
     * @param int $reviewId
     * @param int $clientId
     * @param int $rating
     * @param string|null $comment
     * @return array ['success' => bool, 'review' => array|null, 'errors' => array]
     */
    public function updateReview(int $reviewId, int $clientId, int $rating, ?string $comment): array
    {
        return [
            'success' => false,
            'review'  => null,
            'errors'  => ['authorization' => 'Review editing is no longer available. Please contact support if you need to modify a review.'],
        ];
    }

    /**
     * Add student reply to a review
     *
     * @param int $reviewId
     * @param int $studentId
     * @param string $reply
     * @return array ['success' => bool, 'review' => array|null, 'errors' => array]
     */
    public function addStudentReply(int $reviewId, int $studentId, string $reply): array
    {
        // Validate reply
        $reply = trim($reply);
        if (empty($reply)) {
            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['reply' => 'Reply cannot be empty'],
            ];
        }

        // Get review
        $review = $this->reviewRepository->findById($reviewId);

        if (! $review) {
            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['review' => 'Review not found'],
            ];
        }

        // Check review is for this student
        if ($review['student_id'] != $studentId) {
            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['authorization' => 'You are not authorized to reply to this review'],
            ];
        }

        try {
            // Update review with student reply
            $this->reviewRepository->update($reviewId, [
                'student_reply' => $reply,
            ]);

            // Send notification to client
            $this->emailService->sendReviewReplyNotification($review, [
                'email' => $review['client_email'],
                'name'  => $review['client_name'],
                'reply' => $reply,
            ]);

            // Get the updated review
            $updatedReview = $this->reviewRepository->findById($reviewId);

            return [
                'success' => true,
                'review'  => $updatedReview,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            error_log('Review reply error: ' . $e->getMessage());

            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['database' => 'Failed to add reply. Please try again.'],
            ];
        }
    }

    /**
     * Get reviews for a student
     *
     * @param int $studentId
     * @param int $page
     * @return array
     */
    public function getReviewsForStudent(int $studentId, int $page = 1): array
    {
        return $this->reviewRepository->findByStudentId($studentId, $page, 10);
    }

    /**
     * Calculate and update student average rating
     *
     * @param int $studentId
     * @return float
     */
    public function calculateAverageRating(int $studentId): float
    {
        $avgRating = $this->reviewRepository->calculateAverageRating($studentId);
        $this->reviewRepository->updateStudentRating($studentId);

        return $avgRating;
    }

    /**
     * Get review by ID
     *
     * @param int $reviewId
     * @return array|null
     */
    public function getReviewById(int $reviewId): ?array
    {
        return $this->reviewRepository->findById($reviewId);
    }

    /**
     * Get review by order ID
     *
     * @param int $orderId
     * @return array|null
     */
    public function getReviewByOrderId(int $orderId): ?array
    {
        return $this->reviewRepository->findByOrderId($orderId);
    }

    /**
     * Get total count of reviews for a student
     *
     * @param int $studentId
     * @return int
     */
    public function getReviewCount(int $studentId): int
    {
        return $this->reviewRepository->countByStudentId($studentId);
    }

    /**
     * Flag a review (hide from public display)
     *
     * @param int $reviewId
     * @param int $adminId
     * @param string|null $moderationNotes
     * @return array ['success' => bool, 'review' => array|null, 'errors' => array]
     */
    public function flagReview(int $reviewId, int $adminId, ?string $moderationNotes = null): array
    {
        // Get review
        $review = $this->reviewRepository->findById($reviewId);

        if (! $review) {
            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['review' => 'Review not found'],
            ];
        }

        // Check if already hidden
        if ($review['is_hidden']) {
            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['review' => 'Review is already hidden'],
            ];
        }

        // Begin transaction
        $this->reviewRepository->beginTransaction();

        try {
            // Hide the review
            $this->reviewRepository->hideReview($reviewId, $adminId, $moderationNotes);

            // Recalculate student average_rating (excluding hidden reviews)
            $this->reviewRepository->updateStudentRating($review['student_id']);

            // Log audit action
            $this->logAuditAction($adminId, 'flag_review', 'review', $reviewId, [
                'is_hidden' => 0,
            ], [
                'is_hidden'        => 1,
                'moderation_notes' => $moderationNotes,
            ]);

            // Commit transaction
            $this->reviewRepository->commit();

            // Get the updated review
            $updatedReview = $this->reviewRepository->findById($reviewId);

            return [
                'success' => true,
                'review'  => $updatedReview,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->reviewRepository->rollback();
            error_log('Review flag error: ' . $e->getMessage());

            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['database' => 'Failed to flag review. Please try again.'],
            ];
        }
    }

    /**
     * Unflag a review (restore to public display)
     *
     * @param int $reviewId
     * @param int $adminId
     * @return array ['success' => bool, 'review' => array|null, 'errors' => array]
     */
    public function unflagReview(int $reviewId, int $adminId): array
    {
        // Get review
        $review = $this->reviewRepository->findById($reviewId);

        if (! $review) {
            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['review' => 'Review not found'],
            ];
        }

        // Check if not hidden
        if (! $review['is_hidden']) {
            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['review' => 'Review is not hidden'],
            ];
        }

        // Begin transaction
        $this->reviewRepository->beginTransaction();

        try {
            // Unhide the review
            $this->reviewRepository->unhideReview($reviewId, $adminId);

            // Recalculate student average_rating (including restored review)
            $this->reviewRepository->updateStudentRating($review['student_id']);

            // Log audit action
            $this->logAuditAction($adminId, 'unflag_review', 'review', $reviewId, [
                'is_hidden'        => 1,
                'moderation_notes' => $review['moderation_notes'],
            ], [
                'is_hidden'        => 0,
                'moderation_notes' => null,
            ]);

            // Commit transaction
            $this->reviewRepository->commit();

            // Get the updated review
            $updatedReview = $this->reviewRepository->findById($reviewId);

            return [
                'success' => true,
                'review'  => $updatedReview,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->reviewRepository->rollback();
            error_log('Review unflag error: ' . $e->getMessage());

            return [
                'success' => false,
                'review'  => null,
                'errors'  => ['database' => 'Failed to unflag review. Please try again.'],
            ];
        }
    }

    /**
     * Get all reviews for moderation (with optional filter)
     *
     * @param string|null $filter 'flagged', 'visible', or null for all
     * @param int $page
     * @return array
     */
    public function getAllReviewsForModeration(?string $filter = null, int $page = 1): array
    {
        return $this->reviewRepository->findAllForModeration($filter, $page, 20);
    }

    /**
     * Get reviews for moderation (flagged/hidden reviews)
     *
     * @param int $page
     * @return array
     */
    public function getReviewsForModeration(int $page = 1): array
    {
        return $this->reviewRepository->findFlaggedReviews($page, 20);
    }

    /**
     * Get count of reviews for moderation (with optional filter)
     *
     * @param string|null $filter 'flagged', 'visible', or null for all
     * @return int
     */
    public function getTotalReviewCount(?string $filter = null): int
    {
        return $this->reviewRepository->countAllReviews($filter);
    }

    /**
     * Get count of flagged reviews
     *
     * @return int
     */
    public function getFlaggedReviewCount(): int
    {
        return $this->reviewRepository->countFlaggedReviews();
    }

    /**
     * Log audit action
     *
     * @param int $userId
     * @param string $action
     * @param string $resourceType
     * @param int $resourceId
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return void
     */
    private function logAuditAction(int $userId, string $action, string $resourceType, int $resourceId, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            $sql = "INSERT INTO audit_logs (
                user_id, action, resource_type, resource_id,
                old_values, new_values, ip_address, user_agent, created_at
            ) VALUES (
                :user_id, :action, :resource_type, :resource_id,
                :old_values, :new_values, :ip_address, :user_agent, NOW()
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id'       => $userId,
                'action'        => $action,
                'resource_type' => $resourceType,
                'resource_id'   => $resourceId,
                'old_values'    => $oldValues ? json_encode($oldValues) : null,
                'new_values'    => $newValues ? json_encode($newValues) : null,
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Exception $e) {
            error_log('Audit log error: ' . $e->getMessage());
        }
    }
}
