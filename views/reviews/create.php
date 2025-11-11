<?php
    if (! isset($order)) {
        header('Location: /orders');
        exit;
    }

    // Set page title
    $title = 'Submit Review - Student Skills Marketplace';

    // Start output buffering for additional head content
    ob_start();
?>
    <style>
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.25rem;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            font-size: 2rem;
            color: #d1d5db;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #fbbf24;
        }
    </style>
<?php
    // Capture additional head content
    $additionalHead = ob_get_clean();

    // Start output buffering for content
    ob_start();
?>

<div class="max-w-3xl mx-auto">
    <!-- Back Link -->
    <div class="mb-6">
        <a href="/orders/<?php echo e($order['id']) ?>" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Order
        </a>
    </div>

    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Submit Review</h1>
        <p class="text-gray-600">Share your experience with this order</p>
    </div>

    <!-- Order Summary -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Details</h2>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600">Service:</span>
                <span class="font-medium text-gray-900"><?php echo e($order['service_title']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Student:</span>
                <span class="font-medium text-gray-900"><?php echo e($order['student_name']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Completed:</span>
                <span class="font-medium text-gray-900"><?php echo date('M d, Y', strtotime($order['completed_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- Review Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                <?php echo e($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="/reviews/store" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="order_id" value="<?php echo e($order['id']) ?>">

            <!-- Rating -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Rating <span class="text-red-500">*</span>
                </label>
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5" title="5 stars">★</label>

                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4" title="4 stars">★</label>

                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3" title="3 stars">★</label>

                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2" title="2 stars">★</label>

                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1" title="1 star">★</label>
                </div>
                <p class="mt-2 text-sm text-gray-500">Click on the stars to rate (1-5 stars)</p>
            </div>

            <!-- Comment -->
            <div class="mb-6">
                <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
                    Review Comment (Optional)
                </label>
                <textarea
                    id="comment"
                    name="comment"
                    rows="6"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Share your experience with this service and the student's work..."
                ><?php echo isset($_POST['comment']) ? e($_POST['comment']) : '' ?></textarea>
                <p class="mt-2 text-sm text-gray-500">
                    Your review will help other clients make informed decisions. You can edit your review within 24 hours of submission.
                </p>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <a href="/orders/<?php echo e($order['id']) ?>" class="text-gray-600 hover:text-gray-800">
                    Cancel
                </a>
                <button
                    type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Submit Review
                </button>
            </div>
        </form>
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <svg class="w-5 h-5 text-blue-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">Review Guidelines</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Be honest and constructive in your feedback</li>
                    <li>You can edit your review within 24 hours</li>
                    <li>The student can reply to your review</li>
                    <li>Your review will be visible on the student's profile</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
    // Capture content
    $content = ob_get_clean();

    // No additional scripts needed
    $additionalScripts = '';

    // Include dashboard layout
    include __DIR__ . '/../layouts/dashboard.php';
?>
