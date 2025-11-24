<div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">My Orders</h1>

        <!-- Status Filter Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <a href="/orders" class="<?php echo ! isset($_GET['status']) ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                    All Orders
                </a>
                <!-- <a href="/orders?status=pending" class="<?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                    Pending
                </a> -->
                <a href="/orders?status=in_progress" class="<?php echo isset($_GET['status']) && $_GET['status'] === 'in_progress' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                    In Progress
                </a>
                <a href="/orders?status=delivered" class="<?php echo isset($_GET['status']) && $_GET['status'] === 'delivered' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                    Delivered
                </a>
                <a href="/orders?status=completed" class="<?php echo isset($_GET['status']) && $_GET['status'] === 'completed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                    Completed
                </a>
            </nav>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No orders found</h3>
                <p class="mt-1 text-sm text-gray-500">Start by browsing services and placing an order.</p>
                <div class="mt-6">
                    <a href="/services/search" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Browse Services
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order): ?>
                    <?php
                        $statusColors = [
                            'pending'            => 'bg-yellow-100 text-yellow-800',
                            'in_progress'        => 'bg-blue-100 text-blue-800',
                            'delivered'          => 'bg-purple-100 text-purple-800',
                            'revision_requested' => 'bg-orange-100 text-orange-800',
                            'completed'          => 'bg-green-100 text-green-800',
                            'cancelled'          => 'bg-red-100 text-red-800',
                        ];
                    ?>
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        Order #<?php echo e($order['id']) ?>
                                    </h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              <?php echo $statusColors[$order['status']] ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', e($order['status']))) ?>
                                    </span>
                                </div>

                                <p class="text-gray-700 font-medium mb-2"><?php echo e($order['service_title']) ?></p>

                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <span>Student:                                                                                                                                                                                                                                                                                                                                                               <?php echo e($order['student_name'] ?? explode('@', $order['student_email'] ?? '')[0]) ?></span>
                                    <span>•</span>
                                    <span>Deadline:                                                                                                                                                                                                                                                                                                                                                                      <?php echo date('M d, Y', strtotime($order['deadline'])) ?></span>
                                    <span>•</span>
                                    <span>Created:                                                                                                                                                                                                                                                                                                                                                               <?php echo date('M d, Y', strtotime($order['created_at'])) ?></span>
                                </div>
                            </div>

                            <div class="text-right ml-4">
                                <div class="text-2xl font-bold text-gray-900 mb-2">
                                    $<?php echo safe_number_format($order['price'], 2) ?>
                                </div>
                                <a href="/orders/<?php echo e($order['id']) ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    View Details →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
</div>
