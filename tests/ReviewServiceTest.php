<?php

/**
 * Review Service Test
 *
 * Tests for ReviewService moderation functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/ReviewService.php';
require_once __DIR__ . '/../src/Repositories/ReviewRepository.php';
require_once __DIR__ . '/../src/Repositories/OrderRepository.php';

class ReviewServiceTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private ReviewService $reviewService;
    private ReviewRepository $reviewRepository;
    private OrderRepository $orderRepository;

    public function __construct()
    {
        // Use test database connection
        $this->db               = getDatabaseConnection();
        $this->reviewRepository = new ReviewRepository($this->db);
        $this->orderRepository  = new OrderRepository($this->db);
        $this->reviewService    = new ReviewService($this->reviewRepository, $this->orderRepository);
    }

    public function run(): void
    {
        echo "Running Review Service Tests...\n\n";

        $this->testFlagReviewSetsIsHidden();
        $this->testUnflagReviewClearsIsHidden();
        $this->testClientsCannotUpdateReviews();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testFlagReviewSetsIsHidden(): void
    {
        echo "Test: flagReview sets is_hidden flag\n";

        // Create a test review
        $reviewId = $this->createTestReview();

        if ($reviewId) {
            // Flag the review
            $adminId = 1;
            $result  = $this->reviewService->flagReview($reviewId, $adminId, 'Test moderation');

            // Check result
            $this->assert($result['success'], "Flag review succeeds");
            $this->assert($result['review']['is_hidden'] == 1, "Review is_hidden flag is set to 1");
            $this->assert($result['review']['moderation_notes'] === 'Test moderation', "Moderation notes are saved");
            $this->assert($result['review']['moderated_by'] == $adminId, "Moderated_by is set to admin ID");
            $this->assert(! empty($result['review']['moderated_at']), "Moderated_at timestamp is set");

            // Cleanup
            $this->cleanupTestReview($reviewId);
        } else {
            echo "  ⚠ WARNING: Could not create test review\n";
        }
    }

    private function testUnflagReviewClearsIsHidden(): void
    {
        echo "\nTest: unflagReview clears is_hidden flag\n";

        // Create a test review and flag it
        $reviewId = $this->createTestReview();

        if ($reviewId) {
            $adminId = 1;

            // Flag the review first
            $this->reviewService->flagReview($reviewId, $adminId, 'Test moderation');

            // Unflag the review
            $result = $this->reviewService->unflagReview($reviewId, $adminId);

            // Check result
            $this->assert($result['success'], "Unflag review succeeds");
            $this->assert($result['review']['is_hidden'] == 0, "Review is_hidden flag is cleared");
            $this->assert($result['review']['moderation_notes'] === null, "Moderation notes are cleared");
            $this->assert($result['review']['moderated_by'] == $adminId, "Moderated_by is updated");
            $this->assert(! empty($result['review']['moderated_at']), "Moderated_at timestamp is updated");

            // Cleanup
            $this->cleanupTestReview($reviewId);
        } else {
            echo "  ⚠ WARNING: Could not create test review\n";
        }
    }

    private function testClientsCannotUpdateReviews(): void
    {
        echo "\nTest: Clients cannot update reviews\n";

        // Create a test review
        $reviewId = $this->createTestReview();

        if ($reviewId) {
            // Attempt to update the review
            $clientId = 1;
            $result   = $this->reviewService->updateReview($reviewId, $clientId, 5, 'Updated comment');

            // Check that update is rejected
            $this->assert(! $result['success'], "Update review fails");
            $this->assert(isset($result['errors']['authorization']), "Authorization error is returned");
            $this->assert(
                strpos($result['errors']['authorization'], 'no longer available') !== false,
                "Error message indicates editing is no longer available"
            );

            // Verify review was not changed
            $review = $this->reviewService->getReviewById($reviewId);
            $this->assert($review['rating'] == 4, "Review rating unchanged");
            $this->assert($review['comment'] === 'Test review comment', "Review comment unchanged");

            // Cleanup
            $this->cleanupTestReview($reviewId);
        } else {
            echo "  ⚠ WARNING: Could not create test review\n";
        }
    }

    private function createTestReview(): ?int
    {
        try {
            // Create a test review
            $reviewId = $this->reviewRepository->create([
                'order_id'       => 99997,
                'client_id'      => 1,
                'student_id'     => 2,
                'rating'         => 4,
                'comment'        => 'Test review comment',
                'can_edit_until' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            ]);

            return $reviewId;
        } catch (Exception $e) {
            error_log('Failed to create test review: ' . $e->getMessage());

            return null;
        }
    }

    private function cleanupTestReview(int $reviewId): void
    {
        try {
            $sql  = "DELETE FROM reviews WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $reviewId]);
        } catch (Exception $e) {
            error_log('Failed to cleanup test review: ' . $e->getMessage());
        }
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "  ✓ PASS: $message\n";
            $this->testsPassed++;
        } else {
            echo "  ✗ FAIL: $message\n";
            $this->testsFailed++;
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new ReviewServiceTest();
    $test->run();
}
