<?php
    /**
     * Admin Review Moderation View
     */

    $pageTitle = 'Review Moderation';
    ob_start();
?>


<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-2">
        <h1 class="text-3xl font-bold text-gray-900">Review Moderation</h1>
        <p class="mt-2 text-gray-600">Manage and moderate all platform reviews</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo e($_SESSION['success']);unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo e($_SESSION['error']);unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total Reviews -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalAllReviews) ?></p>
                    <p class="text-gray-600">Total Reviews</p>
                </div>
            </div>
        </div>

        <!-- Visible Reviews -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalVisibleReviews) ?></p>
                    <p class="text-gray-600">Visible Reviews</p>
                </div>
            </div>
        </div>

        <!-- Flagged Reviews -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                </svg>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalFlaggedReviews) ?></p>
                    <p class="text-gray-600">Flagged Reviews</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="?filter="
                   class="px-6 py-4 text-sm font-medium border-b-2                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   <?php echo $filter === null ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    All Reviews (<?php echo number_format($totalAllReviews) ?>)
                </a>
                <a href="?filter=visible"
                   class="px-6 py-4 text-sm font-medium border-b-2                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   <?php echo $filter === 'visible' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    Visible (<?php echo number_format($totalVisibleReviews) ?>)
                </a>
                <a href="?filter=flagged"
                   class="px-6 py-4 text-sm font-medium border-b-2                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   <?php echo $filter === 'flagged' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    Flagged (<?php echo number_format($totalFlaggedReviews) ?>)
                </a>
            </nav>
        </div>
    </div>

    <!-- Results Summary -->
    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Showing                                                                                                                                                                            <?php echo count($reviews) ?> of<?php echo number_format($totalReviews) ?> reviews
        </p>
    </div>

    <!-- Reviews Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                <?php if ($filter === 'flagged'): ?>
                                    No flagged reviews at this time.
                                <?php elseif ($filter === 'visible'): ?>
                                    No visible reviews at this time.
                                <?php else: ?>
                                    No reviews have been submitted yet.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <tr class="hover:bg-gray-50">
                                <!-- Rating -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-yellow-500 text-lg">
                                        <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?>
                                    </span>
                                </td>

                                <!-- Service -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo e($review['service_title']) ?>">
                                        <?php echo e($review['service_title']) ?>
                                    </div>
                                </td>

                                <!-- Client -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo e($review['client_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($review['client_email']) ?></div>
                                </td>

                                <!-- Student -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo e($review['student_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($review['student_email']) ?></div>
                                </td>

                                <!-- Comment -->
                                <td class="px-6 py-4">
                                    <?php if ($review['comment']): ?>
                                        <div class="text-sm text-gray-700 max-w-xs truncate" title="<?php echo e($review['comment']) ?>">
                                            <?php echo e($review['comment']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">No comment</span>
                                    <?php endif; ?>
                                    <?php if ($review['student_reply']): ?>
                                        <div class="text-xs text-blue-600 mt-1">
                                            ↳ Student replied
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($review['is_hidden']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Hidden
                                        </span>
                                        <?php if ($review['moderation_notes']): ?>
                                            <div class="text-xs text-gray-500 mt-1" title="<?php echo e($review['moderation_notes']) ?>">
                                                By                                                                                                                                                                                                                                                                                                                                                                                                                                                                   <?php echo e($review['moderator_name'] ?? 'Admin') ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Visible
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])) ?>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="/orders/<?php echo e($review['order_id']) ?>"
                                           class="text-blue-600 hover:text-blue-900"
                                           title="View Order">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        <?php if ($review['is_hidden']): ?>
                                            <!-- Unflag Button -->
                                            <form method="POST" action="/admin/reviews/<?php echo e($review['id']) ?>/unflag" data-loading
                                                  onsubmit="return confirm('Are you sure you want to restore this review to public display?')"
                                                  class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
                                                <button type="submit"
                                                        class="text-green-600 hover:text-green-900"
                                                        title="Restore Review">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Flag Button -->
                                            <button onclick="showFlagModal(<?php echo $review['id'] ?>)"
                                                    class="text-red-600 hover:text-red-900"
                                                    title="Flag Review">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="flex items-center space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1 ?><?php echo $filter ? '&filter=' . $filter : '' ?>"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i ?><?php echo $filter ? '&filter=' . $filter : '' ?>"
                       class="px-4 py-2 border rounded-md                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                        <?php echo $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1 ?><?php echo $filter ? '&filter=' . $filter : '' ?>"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Next
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Flag Review Modal -->
<div id="flagModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg max-w-2xl w-full mx-4 p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Flag Review</h3>

        <form id="flagForm" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">

            <div class="mb-4">
                <label for="moderation_notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Moderation Notes (Optional)
                </label>
                <textarea
                    id="moderation_notes"
                    name="moderation_notes"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                    placeholder="Explain why this review is being flagged..."
                ></textarea>
                <p class="mt-1 text-sm text-gray-500">
                    These notes are for internal use only and will not be visible to users.
                </p>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <button type="button"
                        onclick="closeFlagModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Flag Review
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showFlagModal(reviewId) {
    const modal = document.getElementById('flagModal');
    const form = document.getElementById('flagForm');
    form.action = '/admin/reviews/' + reviewId + '/flag';
    modal.classList.remove('hidden');
}

function closeFlagModal() {
    const modal = document.getElementById('flagModal');
    const form = document.getElementById('flagForm');
    form.reset();
    modal.classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('flagModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFlagModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeFlagModal();
    }
});
</script>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>

