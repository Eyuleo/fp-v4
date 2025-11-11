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
                                <?php echo strtoupper(substr($profile['email'] ?? 'S', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                <?php echo e(explode('@', $profile['email'] ?? 'Student')[0]) ?>
                            </h1>
                            <p class="text-gray-600">Student • Member since                                                                                                                                                                                                                                        <?php echo e(date('M Y', strtotime($profile['user_created_at'] ?? 'now'))) ?></p>
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

                <!-- Reviews Section -->
                <?php if (! empty($reviews)): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Reviews</h2>
                        <div class="space-y-4">
                            <?php foreach ($reviews as $review): ?>
                                <div class="border-b border-gray-200 pb-4 last:border-b-0">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <svg class="w-4 h-4<?php echo $i <= ($review['rating'] ?? 0) ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-sm text-gray-500">
                                            <?php echo e(date('M d, Y', strtotime($review['created_at'] ?? 'now'))) ?>
                                        </span>
                                    </div>
                                    <?php if (! empty($review['comment'])): ?>
                                        <p class="text-gray-700"><?php echo e($review['comment']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Reviews</h2>
                        <p class="text-gray-600">No reviews yet</p>
                    </div>
                <?php endif; ?>
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
