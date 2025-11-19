<?php
    $pageTitle = 'My Services';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">My Services</h1>
            <p class="mt-2 text-gray-600">Manage your service listings</p>
        </div>
        <a href="/student/services/create" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <span class="inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Service
            </span>
        </a>
    </div>

    <?php require __DIR__ . '/../../partials/alert.php'; ?>

    <?php if (empty($services)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No services yet</h3>
            <p class="mt-2 text-gray-600">Get started by creating your first service listing</p>
            <a href="/student/services/create" class="mt-6 inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Create Your First Service
            </a>
        </div>
    <?php else: ?>
        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($services as $service): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                    <!-- Status Badge -->
                    <div class="p-4 border-b border-gray-200">
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

                    <!-- Service Info -->
                    <div class="p-6">
                        <?php if ($service['status'] === 'rejected' && !empty($service['rejection_reason'])): ?>
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-red-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-xs font-semibold text-red-800 mb-1">Action Required</p>
                                        <p class="text-xs text-red-700 line-clamp-2"><?php echo e($service['rejection_reason']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <span class="text-xs text-gray-500"><?php echo e($service['category_name']) ?></span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                            <?php echo e($service['title']) ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                            <?php echo e($service['description']) ?>
                        </p>

                        <!-- Tags -->
                        <?php if (! empty($service['tags'])): ?>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php foreach (array_slice($service['tags'], 0, 3) as $tag): ?>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
                                        <?php echo e($tag) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($service['tags']) > 3): ?>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
                                        +<?php echo count($service['tags']) - 3 ?> more
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Price and Delivery -->
                        <div class="flex items-center justify-between mb-4 pt-4 border-t border-gray-200">
                            <div>
                                <div class="text-2xl font-bold text-gray-900">
                                    $<?php echo safe_number_format($service['price'], 2) ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">
                                    <?php echo e($service['delivery_days']) ?> day<?php echo $service['delivery_days'] != 1 ? 's' : '' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col space-y-2">
                            <a href="/student/services/<?php echo e($service['id']) ?>" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm text-center">
                                View Details
                            </a>

                            <div class="flex space-x-2">
                                <a href="/student/services/<?php echo e($service['id']) ?>/edit" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm text-center">
                                    Edit
                                </a>
                                <form action="/student/services/<?php echo e($service['id']) ?>/delete" method="POST" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this service? This action cannot be undone.');">
                                    <?php echo csrf_field() ?>
                                    <button type="submit" class="w-full px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition text-sm">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
