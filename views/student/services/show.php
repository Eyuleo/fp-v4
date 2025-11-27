<?php
    $pageTitle = 'Service Details';

    require_once __DIR__ . '/../../../src/Services/FileService.php';
    $fileService = new FileService();

    // Separate image and non-image files, generate signed URLs
    $imageFiles      = [];
    $otherFiles      = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (! empty($service['sample_files']) && is_array($service['sample_files'])) {
        foreach ($service['sample_files'] as $file) {
            // Skip if file data is incomplete
            if (! is_array($file) || empty($file['path']) || empty($file['original_name'])) {
                continue;
            }

            $extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
            $signedUrl = $fileService->generateSignedUrl($file['path'], 1800);

            // Add signed URL for display/download
            $file['signed_url']   = $signedUrl;
            $file['display_path'] = $file['path'];

            if (in_array($extension, $imageExtensions)) {
                $imageFiles[] = $file;
            } else {
                $otherFiles[] = $file;
            }
        }
    }
?>

<div class="max-w-5xl mx-auto"
     x-data="{
        galleryOpen: false,
        currentImage: 0,
        images: [<?php
                     echo implode(',', array_map(
                         fn($f) => '\'' . e($f['signed_url']) . '\'',
                         $imageFiles
                 ));
                 ?>]
     }"
>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="/student/services" class="text-blue-600 hover:text-blue-700 mb-2 inline-block">
                ← Back to Services
            </a>
            <h1 class="text-3xl font-bold text-gray-900"><?php echo e($service['title']) ?></h1>
        </div>
        <!-- Potential extra actions could go here -->
    </div>

    <?php require __DIR__ . '/../../partials/alert.php'; ?>

    <?php if ($service['status'] === 'rejected' && !empty($service['rejection_reason'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-6 mb-6 rounded-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-red-800 mb-2">
                        Service Rejected - Action Required
                    </h3>
                    <div class="text-sm text-red-700 mb-4">
                        <p class="font-medium mb-2">Reason for rejection:</p>
                        <p class="whitespace-pre-wrap bg-white p-3 rounded border border-red-200"><?php echo e($service['rejection_reason']) ?></p>
                    </div>
                    <div class="bg-white p-4 rounded border border-red-200">
                        <p class="text-sm font-semibold text-red-800 mb-2">📋 How to Get Your Service Approved:</p>
                        <ol class="text-sm text-red-700 space-y-1 list-decimal list-inside">
                            <li>Review the rejection reason carefully</li>
                            <li>Click "Edit Service" below to make necessary changes</li>
                            <li>Address all concerns mentioned in the feedback</li>
                            <li>Save your changes to automatically resubmit for review</li>
                        </ol>
                    </div>
                    <div class="mt-4">
                        <a href="/student/services/<?php echo e($service['id']) ?>/edit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Service Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($service['status'] === 'pending'): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-6 rounded-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-blue-800 mb-1">
                        Service Pending Review
                    </h3>
                    <p class="text-sm text-blue-700">
                        Your service is currently being reviewed by our team. You'll receive a notification once it's been approved or if any changes are needed.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
                            'pending'  => 'bg-blue-100 text-blue-800',
                            'rejected' => 'bg-red-100 text-red-800',
                        ];
                        $statusColor = $statusColors[$service['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColor ?>">
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
                                        @click="galleryOpen = true; currentImage = <?php echo (int) $index ?>"
                                    >
                                        <img
                                            src="<?php echo e($file['signed_url']) ?>"
                                            alt="<?php echo e($file['original_name']) ?>"
                                            class="w-full h-full object-cover"
                                        >
                                        <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 transition flex items-center justify-center">
                                            <svg class="w-8 h-8 text-white opacity-0 hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                            </svg>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($otherFiles)): ?>
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Documents</h3>
                            <div class="space-y-2">
                                <?php foreach ($otherFiles as $file): ?>
                                    <?php 
                                        $extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                                        $isPdf = $extension === 'pdf';
                                    ?>
                                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center space-x-3">
                                            <?php if ($isPdf): ?>
                                                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                                </svg>
                                            <?php else: ?>
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                </svg>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo e($file['original_name']) ?></div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo strtoupper($extension) ?> File • <?php echo safe_number_format(($file['size'] ?? 0) / 1024, 1) ?> KB
                                                </div>
                                            </div>
                                        </div>
                                        <a href="<?php echo e($file['signed_url']) ?>" download class="text-blue-600 hover:text-blue-700 text-sm font-medium">
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
                        $<?php echo safe_number_format($service['price'], 2) ?>
                    </div>
                </div>

                <div class="flex items-center text-gray-600 mb-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
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

                    <?php if ($service['status'] === 'active'): ?>
                        <form action="/student/services/<?php echo e($service['id']) ?>/deactivate" method="POST" onsubmit="return confirm('Are you sure you want to deactivate this service? It will no longer be visible to clients.');">
                            <?php echo csrf_field() ?>
                            <button type="submit" class="w-full px-4 py-2 border border-yellow-300 text-yellow-700 rounded-lg hover:bg-yellow-50 transition">
                                Deactivate Service
                            </button>
                        </form>
                    <?php elseif ($service['status'] === 'paused'): ?>
                        <form action="/student/services/<?php echo e($service['id']) ?>/activate" method="POST">
                            <?php echo csrf_field() ?>
                            <button type="submit" class="w-full px-4 py-2 border border-green-300 text-green-700 rounded-lg hover:bg-green-50 transition">
                                Activate Service
                            </button>
                        </form>
                    <?php endif; ?>

                    <form action="/student/services/<?php echo e($service['id']) ?>/delete"
                          method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this service? This action cannot be undone.')">
                        <?php echo csrf_field() ?>
                        <button type="submit"
                                class="w-full px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition">
                            Delete Service
                        </button>
                    </form>
                </div>

                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-xs text-blue-800">
                        <strong>Note:</strong> 
                        <?php if ($service['status'] === 'inactive' || $service['status'] === 'pending'): ?>
                            Service activation requires administrator approval.
                        <?php else: ?>
                            Deactivating a service will hide it from search results but keep it in your list.
                        <?php endif; ?>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Previous Button -->
            <button
                @click="currentImage = currentImage > 0 ? currentImage - 1 : images.length - 1"
                class="absolute left-4 text-white hover:text-gray-300 z-10"
                x-show="images.length > 1"
            >
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 19l-7-7 7-7"/>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5l7 7-7 7"/>
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