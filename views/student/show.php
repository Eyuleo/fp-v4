<?php
    /**
     * Public Student Profile View
     */
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4">
        <!-- Profile Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-4">
                        <?php if (! empty($profile['profile_picture'])): ?>
                            <img
                                src="/storage/file?path=profiles/<?php echo e($profile['user_id']) ?>/<?php echo e($profile['profile_picture']) ?>"
                                alt="Profile picture"
                                class="w-20 h-20 object-cover rounded-full border-2 border-gray-300"
                            >
                        <?php else: ?>
                            <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                <?php echo strtoupper(substr($profile['name'] ?? $profile['email'] ?? 'S', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                <?php echo e($profile['name'] ?? explode('@', $profile['email'] ?? 'Student')[0]) ?>
                            </h1>
                            <p class="text-gray-600">Student • Member since                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <?php echo e(date('M Y', strtotime($profile['user_created_at'] ?? 'now'))) ?></p>
                        </div>
                    </div>

                    <!-- Rating and Stats -->
                    <div class="flex items-center space-x-6 mt-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <span class="ml-1 text-lg font-semibold text-gray-900">
                                <?php echo number_format($profile['average_rating'] ?? 0, 1) ?>
                            </span>
                            <span class="ml-1 text-gray-600">
                                (<?php echo e($profile['total_reviews'] ?? 0) ?> reviews)
                            </span>
                        </div>
                        <div class="text-gray-600">
                            <span class="font-semibold text-gray-900"><?php echo e($profile['total_orders'] ?? 0) ?></span> orders completed
                        </div>
                    </div>
                </div>

                <?php if (auth() && user_role() === 'client'): ?>
                    <a
                        href="/services?student_id=<?php echo e($profile['user_id']) ?>"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        View Services
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - About and Skills -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Bio Section -->
                <?php if (! empty($profile['bio'])): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">About</h2>
                        <p class="text-gray-700 whitespace-pre-line"><?php echo e($profile['bio']) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Portfolio Section -->
                <?php if (! empty($profile['portfolio_files']) && is_array($profile['portfolio_files'])): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Portfolio</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <?php foreach ($profile['portfolio_files'] as $file): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-center h-32 bg-gray-100 rounded-md mb-2">
                                        <?php
                                            $extension = strtolower(pathinfo($file['original_name'] ?? '', PATHINFO_EXTENSION));
                                            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])):
                                        ?>
                                            <img
                                                src="/storage/file?path=profiles/<?php echo e($profile['user_id']) ?>/<?php echo e($file['filename'] ?? '') ?>"
                                                alt="<?php echo e($file['original_name'] ?? '') ?>"
                                                class="max-h-full max-w-full object-contain"
                                            >
                                        <?php else: ?>
                                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-700 truncate" title="<?php echo e($file['original_name'] ?? '') ?>">
                                        <?php echo e($file['original_name'] ?? 'File') ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo e(number_format(($file['size'] ?? 0) / 1024, 2)) ?> KB
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Active Services Section -->
                <?php if (! empty($activeServices)): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Active Services</h2>
                        <div class="space-y-4">
                            <?php foreach ($activeServices as $service): ?>
                                <a href="/services/<?php echo e($service['id']) ?>"
                                   class="block border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-semibold text-gray-900 flex-1">
                                            <?php echo e($service['title']) ?>
                                        </h3>
                                        <span class="text-lg font-bold text-gray-900 ml-4">
                                            ETB                                                                                                                                                                                                                                                                                                                                          <?php echo number_format($service['price'], 2) ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                        <?php echo e(substr($service['description'], 0, 120)) ?><?php echo strlen($service['description']) > 120 ? '...' : '' ?>
                                    </p>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <?php echo e($service['delivery_days']) ?> day<?php echo $service['delivery_days'] != 1 ? 's' : '' ?>
                                        </span>
                                        <?php if (! empty($service['category_name'])): ?>
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                                <?php echo e($service['category_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="/services/search?student_id=<?php echo e($profile['user_id']) ?>"
                               class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                View all services →
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reviews Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Reviews</h2>
                        <?php if (! empty($totalReviews)): ?>
                            <span class="text-sm text-gray-600"><?php echo e($totalReviews) ?> total</span>
                        <?php endif; ?>
                    </div>

                    <?php if (! empty($reviews)): ?>
                        <div class="space-y-6">
                            <?php foreach ($reviews as $review): ?>
                                <div class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0">
                                    <!-- Review Header -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-start space-x-3">
                                            <!-- Reviewer Profile Picture with Initials -->
                                            <?php
                                                // Use client_name if available, otherwise use email username, fallback to Anonymous
                                                $reviewerName = $review['client_name'] ?? null;
                                                if (empty($reviewerName) && ! empty($review['client_email'])) {
                                                    $reviewerName = explode('@', $review['client_email'])[0];
                                                }
                                                if (empty($reviewerName)) {
                                                    $reviewerName = 'Anonymous';
                                                }

                                                $initials = '';
                                                if ($reviewerName !== 'Anonymous') {
                                                    $nameParts = explode(' ', trim($reviewerName));
                                                    $initials  = strtoupper(substr($nameParts[0], 0, 1));
                                                    if (count($nameParts) > 1) {
                                                        $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
                                                    }
                                                } else {
                                                    $initials = 'A';
                                                }
                                            ?>
                                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                                                <?php echo e($initials) ?>
                                            </div>
                                            <div>
                                                <div class="flex items-center mb-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <svg class="w-5 h-5<?php echo $i <= ($review['rating'] ?? 0) ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endfor; ?>
                                                </div>
                                                <p class="text-sm font-medium text-gray-900">
                                                    <?php echo e($reviewerName) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <span class="text-sm text-gray-500">
                                            <?php echo e(date('M d, Y', strtotime($review['created_at'] ?? 'now'))) ?>
                                        </span>
                                    </div>

                                    <!-- Review Comment -->
                                    <?php if (! empty($review['comment'])): ?>
                                        <p class="text-gray-700 mb-3"><?php echo e($review['comment']) ?></p>
                                    <?php endif; ?>

                                    <!-- Service Info -->
                                    <?php if (! empty($review['service_title'])): ?>
                                        <p class="text-sm text-gray-500 mb-3">
                                            Service: <span class="font-medium"><?php echo e($review['service_title']) ?></span>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Admin Flag Button -->
                                    <?php if (auth() && Auth::user()['role'] === 'admin'): ?>
                                        <div class="mb-3">
                                            <form method="POST" action="/admin/reviews/<?php echo e($review['id']) ?>/flag" data-loading
                                                  onsubmit="return confirm('Are you sure you want to flag and hide this review?')"
                                                  class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
                                                <button type="submit"
                                                        class="text-sm text-red-600 hover:text-red-800 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                                    </svg>
                                                    Flag Review
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Student Reply -->
                                    <?php if (! empty($review['student_reply'])): ?>
                                        <div class="mt-4 ml-6 pl-4 border-l-2 border-blue-200 bg-blue-50 p-3 rounded-r">
                                            <div class="flex items-center mb-2">
                                                <svg class="w-4 h-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="text-sm font-medium text-blue-900">Student's Reply</span>
                                            </div>
                                            <p class="text-sm text-gray-700"><?php echo e($review['student_reply']) ?></p>
                                            <?php if (! empty($review['student_replied_at'])): ?>
                                                <p class="text-xs text-gray-500 mt-2">
                                                    <?php echo e(date('M d, Y', strtotime($review['student_replied_at']))) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif (auth() && user_id() == $profile['user_id']): ?>
                                        <!-- Reply Form (only visible to profile owner) -->
                                        <div class="mt-4 ml-6">
                                            <form action="/reviews/<?php echo e($review['id']) ?>/reply" method="POST" class="space-y-3">
                                                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token'] ?? '') ?>">
                                                <textarea
                                                    name="student_reply"
                                                    rows="3"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                                    placeholder="Reply to this review..."
                                                    required
                                                ></textarea>
                                                <button
                                                    type="submit"
                                                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                >
                                                    Post Reply
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if (! empty($totalPages) && $totalPages > 1): ?>
                            <div class="mt-6 flex items-center justify-center space-x-2">
                                <?php if ($currentPage > 1): ?>
                                    <a
                                        href="?id=<?php echo e($profile['user_id']) ?>&page=<?php echo e($currentPage - 1) ?>"
                                        class="px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i == $currentPage): ?>
                                        <span class="px-3 py-2 bg-blue-600 text-white rounded-md text-sm">
                                            <?php echo e($i) ?>
                                        </span>
                                    <?php elseif ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 2): ?>
                                        <a
                                            href="?id=<?php echo e($profile['user_id']) ?>&page=<?php echo e($i) ?>"
                                            class="px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                                        >
                                            <?php echo e($i) ?>
                                        </a>
                                    <?php elseif (abs($i - $currentPage) == 3): ?>
                                        <span class="px-2 text-gray-500">...</span>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <a
                                        href="?id=<?php echo e($profile['user_id']) ?>&page=<?php echo e($currentPage + 1) ?>"
                                        class="px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                            <p class="text-gray-600">No reviews yet</p>
                            <p class="text-sm text-gray-500 mt-1">Reviews will appear here after completed orders</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Skills and Info -->
            <div class="space-y-6">
                <!-- Skills Section -->
                <?php if (! empty($profile['skills']) && is_array($profile['skills'])): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Skills</h2>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($profile['skills'] as $skill): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    <?php echo e($skill) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Payment Status -->
                <?php if (! empty($profile['stripe_onboarding_complete'])): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Verification</h2>
                        <div class="flex items-center space-x-2 text-green-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="font-medium">Payment verified</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
