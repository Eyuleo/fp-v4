<?php
    require_once __DIR__ . '/../../../src/Services/FileService.php';
    $fileService = new FileService();

    // Precompute signed URLs for sample files
    $processedSamples = [];
    if (! empty($service['sample_files']) && is_array($service['sample_files'])) {
        foreach ($service['sample_files'] as $file) {
            if (! is_array($file) || empty($file['path'])) {
                continue;
            }
            $file['signed_url'] = $fileService->generateSignedUrl($file['path'], 1800);
            $processedSamples[] = $file;
        }
    }
    $service['sample_files'] = $processedSamples;
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="mb-6 text-sm">
        <ol class="flex items-center space-x-2 text-gray-600">
            <li><a href="/" class="hover:text-blue-600">Home</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="/services/search" class="hover:text-blue-600">Services</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900"><?php echo e($service['title']) ?></li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Service Header -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="mb-4">
                    <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                        <?php echo e($service['category_name']) ?>
                    </span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo e($service['title']) ?></h1>

                <!-- Student Info -->
                <a href="/student/profile?id=<?php echo e($service['student_id']) ?>" class="flex items-center mb-6 hover:bg-gray-50 p-3 rounded-lg transition-colors -ml-3">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-lg">
                        <?php echo strtoupper(substr($service['student_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="ml-4">
                        <p class="font-medium text-gray-900 hover:text-blue-600"><?php echo e($service['student_name'] ?? 'Unknown') ?></p>
                        <div class="flex items-center">
                            <span class="text-yellow-400">★</span>
                            <span class="text-sm text-gray-600 ml-1">
                                <?php echo safe_number_format($service['average_rating'] ?? 0, 1) ?>
                                (<?php echo e($service['total_reviews'] ?? 0) ?> reviews)
                            </span>
                            <span class="mx-2 text-gray-400">•</span>
                            <span class="text-sm text-gray-600">
                                <?php echo e($service['total_orders'] ?? 0) ?> orders completed
                            </span>
                        </div>
                    </div>
                </a>

                <!-- Description -->
                <div class="prose max-w-none">
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">About This Service</h2>
                    <p class="text-gray-700 whitespace-pre-line"><?php echo e($service['description']) ?></p>
                </div>

                <!-- Sample Files Gallery -->
                <?php if (! empty($service['sample_files'])): ?>
                    <div class="mt-6" x-data="{ selectedImage: null }">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Sample Work</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <?php foreach ($service['sample_files'] as $index => $file): ?>
                                <?php
                                    if (! is_array($file) || empty($file['signed_url'])) {
                                        continue;
                                    }

                                    $originalName = $file['original_name'] ?? $file['filename'] ?? basename($file['path']);
                                    $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                    $isImage      = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                ?>
                                <?php if ($isImage): ?>
                                    <div class="relative group cursor-pointer"
                                         @click="selectedImage = '<?php echo e($file['signed_url']) ?>'">
                                        <img
                                            src="<?php echo e($file['signed_url']) ?>"
                                            alt="Sample"
                                            class="w-full h-48 object-cover rounded-lg shadow-sm group-hover:shadow-md transition-shadow"
                                        >
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-opacity rounded-lg flex items-center justify-center">
                                            <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                            </svg>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo e($file['signed_url']) ?>"
                                       download
                                       class="flex flex-col items-center justify-center h-48 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-sm text-gray-600 text-center break-all"><?php echo e($originalName) ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Image Modal -->
                        <div x-show="selectedImage"
                             x-cloak
                             @click="selectedImage = null"
                             class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
                            <div class="relative max-w-4xl max-h-full">
                                <img :src="selectedImage" class="max-w-full max-h-screen object-contain rounded-lg">
                                <button @click.stop="selectedImage = null"
                                        class="absolute top-4 right-4 text-white bg-black bg-opacity-50 rounded-full p-2 hover:bg-opacity-75">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tags -->
                <?php if (! empty($service['tags'])): ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($service['tags'] as $tag): ?>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">
                                    <?php echo e($tag) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- About the Student -->
            <?php if (! empty($service['bio'])): ?>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">About the Student</h2>
                    <p class="text-gray-700 mb-4"><?php echo e($service['bio']) ?></p>

                    <?php if (! empty($service['skills'])): ?>
                        <div class="mt-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Skills</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($service['skills'] as $skill): ?>
                                    <span class="px-3 py-1 bg-blue-50 text-blue-700 text-sm rounded">
                                        <?php echo e($skill) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Reviews -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Reviews</h2>
                <?php if (empty($reviews)): ?>
                    <p class="text-gray-600">No reviews yet. Be the first to order and review this service!</p>
                <?php else: ?>
                    <div class="space-y-6" x-data="{ showAll: false }">
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="border-b border-gray-200 pb-6 last:border-0 last:pb-0"
                                 <?php if ($index > 0): ?>
                                     x-show="showAll"
                                     x-transition
                                 <?php endif; ?>>
                                <div class="flex items-start space-x-3 mb-2">
                                    <?php
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
                                    <div class="flex-1">
                                        <div class="flex items-center mb-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <svg class="w-5 h-5 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.7 1.8-1.59 1.118L10 13.347l-2.49 1.618c-.89.683-1.89-.197-1.59-1.118l1.07-3.292a1 1 0 00-.364-1.118L3.827 8.72c-.783-.57-.38-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z"/>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <span class="font-medium text-gray-900"><?php echo e($reviewerName) ?></span>
                                            <span class="mx-2 text-gray-400">•</span>
                                            <span><?php echo date('M d, Y', strtotime($review['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php if (! empty($review['comment'])): ?>
                                    <p class="text-gray-700 mb-2"><?php echo e($review['comment']) ?></p>
                                <?php endif; ?>
                                <?php if (! empty($review['student_reply'])): ?>
                                    <div class="mt-3 ml-6 p-3 bg-gray-50 rounded">
                                        <p class="text-sm font-medium text-gray-900 mb-1">Student's Response:</p>
                                        <p class="text-sm text-gray-700"><?php echo e($review['student_reply']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($reviews) > 1): ?>
                            <div class="pt-4">
                                <button @click="showAll = !showAll"
                                        class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center">
                                    <span x-text="showAll ? 'Show Less' : 'View More Reviews (<?php echo count($reviews) - 1 ?> more)'"></span>
                                    <svg class="w-4 h-4 ml-1 transition-transform" :class="{ 'rotate-180': showAll }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
                <!-- Price -->
                <div class="mb-6">
                    <p class="text-sm text-gray-600 mb-1">Starting at</p>
                    <p class="text-3xl font-bold text-gray-900">$<?php echo safe_number_format($service['price'], 2) ?></p>
                </div>

                <!-- Service Details -->
                <div class="space-y-4 mb-6 pb-6 border-b border-gray-200">
                    <div class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span><?php echo e($service['delivery_days']) ?> day<?php echo $service['delivery_days'] > 1 ? 's' : '' ?> delivery</span>
                    </div>
                    <div class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>3 revisions included</span>
                    </div>
                </div>

                <!-- Order Button -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'client'): ?>
                        <a href="/orders/create?service_id=<?php echo e($service['id']) ?>"
                           class="block w-full bg-blue-600 text-white text-center px-6 py-3 rounded-lg hover:bg-blue-700 font-medium transition-colors">
                            Order Now
                        </a>
                    <?php else: ?>
                        <div class="text-center text-gray-600 text-sm">
                            Only clients can place orders
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/auth/login?redirect=/services/<?php echo e($service['id']) ?>"
                       class="block w-full bg-blue-600 text-white text-center px-6 py-3 rounded-lg hover:bg-blue-700 font-medium transition-colors">
                        Login to Order
                    </a>
                    <p class="text-center text-sm text-gray-600 mt-3">
                        Don't have an account?
                        <a href="/auth/register" class="text-blue-600 hover:text-blue-700">Sign up</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>