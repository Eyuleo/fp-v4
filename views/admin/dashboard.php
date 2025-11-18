<?php
    /**
     * Admin Dashboard View
     *
     * @var array $analytics Analytics data
     */

    $pageTitle = 'Admin Dashboard';
    ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-2 text-sm text-gray-600">Platform analytics and metrics</p>
    </div>

    <!-- Date Range Filter -->
    <div class="mb-6 bg-white rounded-lg shadow p-4">
        <form method="GET" action="/admin/dashboard" class="flex flex-wrap gap-4 items-end">
            <div>
                <label for="range" class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                <select name="range" id="range" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleCustomDates(this.value)">
                    <option value="7"                                                                                                                                                     <?php echo $analytics['range'] === '7' ? 'selected' : '' ?>>Last 7 days</option>
                    <option value="30"                                                                                                                                                         <?php echo $analytics['range'] === '30' ? 'selected' : '' ?>>Last 30 days</option>
                    <option value="90"                                                                                                                                                         <?php echo $analytics['range'] === '90' ? 'selected' : '' ?>>Last 90 days</option>
                    <option value="custom"                                                                                                                                                                         <?php echo $analytics['range'] === 'custom' ? 'selected' : '' ?>>Custom range</option>
                </select>
            </div>

            <div id="customDates" style="display:                                                                                                                                                                                                     <?php echo $analytics['range'] === 'custom' ? 'flex' : 'none' ?>;" class="gap-4">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo e($analytics['date_from']) ?>" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo e($analytics['date_to']) ?>" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Apply Filter
            </button>
        </form>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- GMV Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Gross Merchandise Value</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">$<?php echo safe_number_format($analytics['gmv'], 2) ?></p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Orders Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Orders</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo safe_number_format($analytics['total_orders'], 0) ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo safe_number_format($analytics['completed_orders'], 0) ?> completed</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completion Rate Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Completion Rate</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo safe_number_format($analytics['completion_rate'], 1) ?>%</p>
                    <p class="text-xs text-gray-500 mt-1">On-time:                                                                                                                                                                                                                                                                         <?php echo safe_number_format($analytics['on_time_rate'], 1) ?>%</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>



    <!-- Recent Orders Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($analytics['recent_orders'])): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                No orders found for the selected period
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($analytics['recent_orders'] as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="/orders/<?php echo $order['id'] ?>" class="text-blue-600 hover:text-blue-800">#<?php echo $order['id'] ?></a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo e($order['service_title']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo e($order['client_name'] ?? explode('@', $order['client_email'] ?? '')[0]) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo e($order['student_name'] ?? explode('@', $order['student_email'] ?? '')[0]) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $statusColors = [
                                            'pending'            => 'bg-yellow-100 text-yellow-800',
                                            'in_progress'        => 'bg-blue-100 text-blue-800',
                                            'delivered'          => 'bg-purple-100 text-purple-800',
                                            'revision_requested' => 'bg-orange-100 text-orange-800',
                                            'completed'          => 'bg-green-100 text-green-800',
                                            'cancelled'          => 'bg-red-100 text-red-800',
                                        ];
                                        $colorClass = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full<?php echo $colorClass ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    $<?php echo safe_number_format($order['price'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($order['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleCustomDates(value) {
    const customDates = document.getElementById('customDates');
    if (value === 'custom') {
        customDates.style.display = 'flex';
    } else {
        customDates.style.display = 'none';
    }
}
</script>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../layouts/admin.php';
?>
