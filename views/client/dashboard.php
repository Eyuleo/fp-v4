<!-- Client Dashboard Content -->
<div class="space-y-6">
    <!-- Profile Section -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center space-x-4">

            <!-- Welcome Message -->
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">
                    Welcome back<?php echo ! empty($user['name']) ? ', ' . htmlspecialchars($user['name']) : '' ?>!
                </h1>
                <p class="text-gray-600">Browse services, manage your orders, and connect with talented students.</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="/services/search" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Browse Services</h3>
                    <p class="text-sm text-gray-600">Find the perfect service</p>
                </div>
            </div>
        </a>

        <a href="/client/orders" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">My Orders</h3>
                    <p class="text-sm text-gray-600">Track your orders</p>
                </div>
            </div>
        </a>

        <a href="/messages" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Messages</h3>
                    <p class="text-sm text-gray-600">Chat with students</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Activity / Orders -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Recent Orders</h2>
            <?php if (! empty($recentOrders)): ?>
                <a href="/orders" class="text-sm text-blue-600 hover:text-blue-700">View all orders →</a>
            <?php endif; ?>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No orders yet</h3>
                <p class="mt-2 text-gray-600">Start by browsing services and placing your first order.</p>
                <div class="mt-6">
                    <a href="/services/search" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Browse Services
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Service
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Student
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Deadline
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentOrders as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo htmlspecialchars($order['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($order['service_title']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($order['student_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $statusColors = [
                                            'pending'     => 'bg-yellow-100 text-yellow-800',
                                            'in_progress' => 'bg-blue-100 text-blue-800',
                                            'delivered'   => 'bg-purple-100 text-purple-800',
                                            'completed'   => 'bg-green-100 text-green-800',
                                            'cancelled'   => 'bg-red-100 text-red-800',
                                            'disputed'    => 'bg-orange-100 text-orange-800',
                                        ];
                                        $statusColor = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full<?php echo $statusColor; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php
                                        $deadline = new DateTime($order['deadline']);
                                        echo htmlspecialchars($deadline->format('M d, Y'));
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    $<?php echo number_format($order['price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="/orders/<?php echo htmlspecialchars($order['id']); ?>" class="text-blue-600 hover:text-blue-900">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
