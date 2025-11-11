<?php
    /**
     * Admin Service Moderation Interface
     *
     * @var array $services List of services
     * @var array $categories List of categories
     * @var int $page Current page
     * @var int $totalPages Total pages
     * @var int $totalCount Total count
     * @var string|null $status Status filter
     * @var int|null $categoryId Category filter
     */

    $pageTitle = 'Service Moderation';
    ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Service Moderation</h1>
        <p class="text-gray-600 mt-2">Review and moderate service listings</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="/admin/services" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Statuses</option>
                    <option value="active"                                                                                     <?php echo($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive"                                                                                         <?php echo($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="paused"                                                                                     <?php echo($status ?? '') === 'paused' ? 'selected' : '' ?>>Paused</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select name="category_id" id="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo e($category['id']) ?>"<?php echo($categoryId ?? '') == $category['id'] ? 'selected' : '' ?>>
                            <?php echo e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Services Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($services)): ?>
            <div class="p-8 text-center text-gray-500">
                <p>No services found.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Orders</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($services as $service): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo e($service['title']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500 truncate max-w-xs">
                                        <?php echo e(substr($service['description'], 0, 100)) ?><?php echo strlen($service['description']) > 100 ? '...' : '' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo e($service['student_name'] ?? $service['student_email']) ?>
                                    </div>
                                    <?php if ($service['average_rating']): ?>
                                        <div class="text-sm text-gray-500">
                                            ⭐                                                                                               <?php echo number_format($service['average_rating'], 1) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo e($service['category_name']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    $<?php echo number_format($service['price'], 2) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                        $statusColors = [
                                            'active'   => 'bg-green-100 text-green-800',
                                            'inactive' => 'bg-gray-100 text-gray-800',
                                            'paused'   => 'bg-yellow-100 text-yellow-800',
                                        ];
                                        $statusColor = $statusColors[$service['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full<?php echo $statusColor ?>">
                                        <?php echo e(ucfirst($service['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo (int) $service['active_orders_count'] ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($service['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="/admin/services/<?php echo e($service['id']) ?>" class="text-blue-600 hover:text-blue-800">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing page                                                                                 <?php echo $page ?> of<?php echo $totalPages ?> (<?php echo $totalCount ?> total services)
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $categoryId ? '&category_id=' . urlencode($categoryId) : '' ?>"
                                   class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $categoryId ? '&category_id=' . urlencode($categoryId) : '' ?>"
                                   class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
