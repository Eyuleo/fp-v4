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
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Browse Services -->
            <a href="/services/search" class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 hover:border-blue-500">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-search text-white text-xl"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400 group-hover:text-blue-500 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Browse Services</h3>
                    <p class="text-sm text-gray-600">Find the perfect service</p>
                </div>
            </a>

            <!-- My Orders -->
            <a href="/client/orders" class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 hover:border-green-500">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-shopping-cart text-white text-xl"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400 group-hover:text-green-500 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">My Orders</h3>
                    <p class="text-sm text-gray-600">Track your orders</p>
                </div>
            </a>

            <!-- Messages -->
            <a href="/messages" class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 hover:border-purple-500">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-envelope text-white text-xl"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400 group-hover:text-purple-500 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Messages</h3>
                    <p class="text-sm text-gray-600">Chat with students</p>
                </div>
            </a>

            <!-- My Profile -->
            <a href="/client/profile" class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 hover:border-indigo-500">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-user text-white text-xl"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">My Profile</h3>
                    <p class="text-sm text-gray-600">View and edit profile</p>
                </div>
            </a>
        </div>
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
                                    $<?php echo safe_number_format($order['price'], 2); ?>
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
