<?php
    /**
     * Admin Order Management Interface
     *
     * @var array $orders List of orders
     * @var int $page Current page
     * @var int $totalPages Total pages
     * @var int $totalCount Total count
     * @var string|null $status Status filter
     * @var string|null $dateFrom Date from filter
     * @var string|null $dateTo Date to filter
     */

    $pageTitle = 'Order Management';
    ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Order Management</h1>
        <p class="text-gray-600 mt-2">View and manage all orders</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="/admin/orders" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Statuses</option>
                    <option value="pending"                                                                                       <?php echo($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="in_progress"                                                                                               <?php echo($status ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="delivered"                                                                                           <?php echo($status ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="revision_requested"                                                                                                             <?php echo($status ?? '') === 'revision_requested' ? 'selected' : '' ?>>Revision Requested</option>
                    <option value="completed"                                                                                           <?php echo($status ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled"                                                                                           <?php echo($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <!-- Date From Filter -->
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo e($dateFrom ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Date To Filter -->
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo e($dateTo ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Submit Button -->
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($orders)): ?>
            <div class="p-8 text-center text-gray-500">
                <p>No orders found.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    #<?php echo e($order['id']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo e($order['service_title']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo e($order['client_name'] ?? explode('@', $order['client_email'] ?? '')[0]) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo e($order['student_name'] ?? explode('@', $order['student_email'] ?? '')[0]) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    $<?php echo number_format($order['price'], 2) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                        $statusColors = [
                                            'pending'            => 'bg-yellow-100 text-yellow-800',
                                            'in_progress'        => 'bg-blue-100 text-blue-800',
                                            'delivered'          => 'bg-purple-100 text-purple-800',
                                            'revision_requested' => 'bg-orange-100 text-orange-800',
                                            'completed'          => 'bg-green-100 text-green-800',
                                            'cancelled'          => 'bg-red-100 text-red-800',
                                        ];
                                        $statusColor = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full<?php echo $statusColor ?>">
                                        <?php echo e(ucfirst(str_replace('_', ' ', $order['status']))) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($order['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="/orders/<?php echo e($order['id']) ?>" class="text-blue-600 hover:text-blue-800">
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
                            Showing page                                                                                 <?php echo $page ?> of<?php echo $totalPages ?> (<?php echo $totalCount ?> total orders)
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : '' ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : '' ?>"
                                   class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : '' ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : '' ?>"
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
