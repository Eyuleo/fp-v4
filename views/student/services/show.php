<?php
    $pageTitle = 'Service Details';

    // Separate image and non-image files
    $imageFiles      = [];
    $otherFiles      = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (! empty($service['sample_files']) && is_array($service['sample_files'])) {
        foreach ($service['sample_files'] as $file) {
            $extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
            // Use path as-is (new FileService format: services/2/filename.ext)
            $file['display_path'] = $file['path'];

            if (in_array($extension, $imageExtensions)) {
                $imageFiles[] = $file;
            } else {
                $otherFiles[] = $file;
            }
        }
    }
?>

<div class="max-w-5xl mx-auto" x-data="{ galleryOpen: false, currentImage: 0, images:                                                                                                                                                                                                                                                                                                                                                                                                                                          <?php echo htmlspecialchars(json_encode(array_map(function ($f) {return '/storage/file?path=' . urlencode($f['display_path']);}, $imageFiles)), ENT_QUOTES, 'UTF-8') ?> }">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="/student/services" class="text-blue-600 hover:text-blue-700 mb-2 inline-block">
                ← Back to Services
            </a>
            <h1 class="text-3xl font-bold text-gray-900"><?php echo e($service['title']) ?></h1>
        </div>
        <!-- <div class="flex space-x-3">
            <a href="/student/services/<?php echo e($service['id']) ?>/edit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Edit Service
            </a>
        </div> -->
    </div>

    <?php require __DIR__ . '/../../partials/alert.php'; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Service Info Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="mb-4">
                    <?php
                        $statusColors = [
                            'active'   => 'bg-green-100 text-green-800',
                            'inactive' => 'bg-gray-100 text-gray-800',
                            'paused'   => 'bg-yellow-100 text-yellow-800',
                        ];
                        $statusColor = $statusColors[$service['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium<?php echo $statusColor ?>">
                        <?php echo ucfirst(e($service['status'])) ?>
                    </span>
                </div>

                <h2 class="text-xl font-semibold text-gray-900 mb-4">Description</h2>
                <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($service['description']) ?></p>

                <?php if (! empty($service['tags'])): ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($service['tags'] as $tag): ?>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                    <?php echo e($tag) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sample Work -->
            <?php if (! empty($imageFiles) || ! empty($otherFiles)): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Sample Work</h2>

                    <?php if (! empty($imageFiles)): ?>
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Images</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($imageFiles as $index => $file): ?>
                                    <div
                                        class="relative aspect-square rounded-lg overflow-hidden cursor-pointer hover:opacity-90 transition"
                                        @click="galleryOpen = true; currentImage =                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   <?php echo $index ?>"
                                    >
                                        <img
                                            src="/storage/file?path=<?php echo urlencode($file['display_path']) ?>"
                                            alt="<?php echo e($file['original_name']) ?>"
                                            class="w-full h-full object-cover"
                                        >
                                        <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 transition flex items-center justify-center">
                                            <svg class="w-8 h-8 text-white opacity-0 hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                            </svg>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($otherFiles)): ?>
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Other Files</h3>
                            <div class="space-y-2">
                                <?php foreach ($otherFiles as $file): ?>
                                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo e($file['original_name']) ?></div>
                                                <div class="text-xs text-gray-500"><?php echo number_format($file['size'] / 1024, 1) ?> KB</div>
                                            </div>
                                        </div>
                                        <a href="/storage/file?path=<?php echo urlencode($file['display_path']) ?>" download class="text-blue-600 hover:text-blue-700 text-sm">
                                            Download
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Pricing Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Pricing & Delivery</h2>

                <div class="mb-4">
                    <div class="text-3xl font-bold text-gray-900">
                        $<?php echo number_format($service['price'], 2) ?>
                    </div>
                </div>

                <div class="flex items-center text-gray-600 mb-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?php echo e($service['delivery_days']) ?> day<?php echo $service['delivery_days'] != 1 ? 's' : '' ?> delivery</span>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <div class="text-sm text-gray-600">
                        <div class="flex justify-between mb-2">
                            <span>Category:</span>
                            <span class="font-medium text-gray-900"><?php echo e($service['category_name'] ?? 'N/A') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Created:</span>
                            <span class="font-medium text-gray-900"><?php echo date('M d, Y', strtotime($service['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>

                <div class="space-y-3">
                    <a href="/student/services/<?php echo e($service['id']) ?>/edit" class="block w-full px-4 py-2 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 transition">
                        Edit Service
                    </a>

                    <form action="/student/services/<?php echo e($service['id']) ?>/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this service? This action cannot be undone.');">
                        <?php echo csrf_field() ?>
                        <button type="submit" class="w-full px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition">
                            Delete Service
                        </button>
                    </form>
                </div>

                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-xs text-blue-800">
                        <strong>Note:</strong> Service activation will be managed by administrators to ensure quality.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Gallery Modal -->
    <div
        x-show="galleryOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="galleryOpen = false"
        @keydown.arrow-left.window="currentImage = currentImage > 0 ? currentImage - 1 : images.length - 1"
        @keydown.arrow-right.window="currentImage = currentImage < images.length - 1 ? currentImage + 1 : 0"
    >
        <!-- Backdrop -->
        <div
            class="absolute inset-0 bg-black bg-opacity-90"
            @click="galleryOpen = false"
        ></div>

        <!-- Modal Content -->
        <div class="relative h-full flex items-center justify-center p-4">
            <!-- Close Button -->
            <button
                @click="galleryOpen = false"
                class="absolute top-4 right-4 text-white hover:text-gray-300 z-10"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Previous Button -->
            <button
                @click="currentImage = currentImage > 0 ? currentImage - 1 : images.length - 1"
                class="absolute left-4 text-white hover:text-gray-300 z-10"
                x-show="images.length > 1"
            >
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <!-- Image -->
            <div class="max-w-6xl max-h-full">
                <template x-for="(image, index) in images" :key="index">
                    <img
                        x-show="currentImage === index"
                        :src="image"
                        class="max-w-full max-h-[90vh] object-contain"
                        alt="Gallery image"
                    >
                </template>
            </div>

            <!-- Next Button -->
            <button
                @click="currentImage = currentImage < images.length - 1 ? currentImage + 1 : 0"
                class="absolute right-4 text-white hover:text-gray-300 z-10"
                x-show="images.length > 1"
            >
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <!-- Image Counter -->
            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white text-sm" x-show="images.length > 1">
                <span x-text="currentImage + 1"></span> / <span x-text="images.length"></span>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] {
    display: none !important;
}
</style>
