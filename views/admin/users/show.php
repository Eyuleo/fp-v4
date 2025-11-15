<?php
    /**
     * Admin User Detail View
     */

    $pageTitle = 'User Details - ' . e($user['email']);
?>

<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="/admin/users" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Users
        </a>
    </div>

    <!-- User Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo e($user['name'] ?? explode('@', $user['email'])[0]) ?></h1>
                <p class="text-sm text-gray-600"><?php echo e($user['email']) ?></p>
                <div class="flex items-center space-x-4 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        <?php if ($user['role'] === 'admin'): ?>
                            bg-purple-100 text-purple-800
                        <?php elseif ($user['role'] === 'student'): ?>
                            bg-blue-100 text-blue-800
                        <?php else: ?>
                            bg-green-100 text-green-800
                        <?php endif; ?>
                    ">
                        <?php echo e(ucfirst($user['role'])) ?>
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        <?php if ($user['status'] === 'active'): ?>
                            bg-green-100 text-green-800
                        <?php elseif ($user['status'] === 'suspended'): ?>
                            bg-red-100 text-red-800
                        <?php else: ?>
                            bg-yellow-100 text-yellow-800
                        <?php endif; ?>
                    ">
                        <?php echo e(ucfirst($user['status'])) ?>
                    </span>
                </div>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><strong>User ID:</strong>                                                 <?php echo e($user['id']) ?></p>
                    <p><strong>Registered:</strong>                                                    <?php echo e(date('F d, Y \a\t g:i A', strtotime($user['created_at']))) ?></p>
                    <?php if ($user['email_verified_at']): ?>
                        <p><strong>Email Verified:</strong><?php echo e(date('F d, Y \a\t g:i A', strtotime($user['email_verified_at']))) ?></p>
                    <?php else: ?>
                        <p class="text-yellow-600"><strong>Email:</strong> Not verified</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <?php if ($user['role'] !== 'admin'): ?>
                <div class="flex space-x-3">
                    <?php if ($user['status'] === 'suspended'): ?>
                        <form method="POST" action="/admin/users/<?php echo e($user['id']) ?>/reactivate" onsubmit="return confirm('Are you sure you want to reactivate this user?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                Reactivate User
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/admin/users/<?php echo e($user['id']) ?>/suspend" onsubmit="return confirm('Are you sure you want to suspend this user? They will be logged out immediately.');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                Suspend User
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm font-medium text-gray-500 mb-1">Total Orders</div>
            <div class="text-3xl font-bold text-gray-900"><?php echo $stats['total_orders'] ?></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm font-medium text-gray-500 mb-1">Completed Orders</div>
            <div class="text-3xl font-bold text-green-600"><?php echo $stats['completed_orders'] ?></div>
        </div>
        <?php if ($user['role'] === 'student'): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="text-sm font-medium text-gray-500 mb-1">Total Services</div>
                <div class="text-3xl font-bold text-blue-600"><?php echo $stats['total_services'] ?></div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="text-sm font-medium text-gray-500 mb-1">Average Rating</div>
                <div class="text-3xl font-bold text-yellow-600">
                    <?php echo $studentProfile ? number_format($studentProfile['average_rating'], 2) : 'N/A' ?>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="text-sm font-medium text-gray-500 mb-1">Total Reviews</div>
                <div class="text-3xl font-bold text-yellow-600"><?php echo $stats['total_reviews'] ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Student Profile (if applicable) -->
    <?php if ($user['role'] === 'student' && $studentProfile): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Student Profile</h2>
            <div class="space-y-3">
                <?php if ($studentProfile['bio']): ?>
                    <div>
                        <strong class="text-gray-700">Bio:</strong>
                        <p class="text-gray-600 mt-1"><?php echo e($studentProfile['bio']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($studentProfile['skills']): ?>
                    <div>
                        <strong class="text-gray-700">Skills:</strong>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <?php foreach (json_decode($studentProfile['skills'], true) as $skill): ?>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                    <?php echo e($skill) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div>
                    <strong class="text-gray-700">Stripe Connect:</strong>
                    <span class="<?php echo $studentProfile['stripe_onboarding_complete'] ? 'text-green-600' : 'text-yellow-600' ?>">
                        <?php echo $studentProfile['stripe_onboarding_complete'] ? 'Connected' : 'Not Connected' ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Services (if student) -->
    <?php if ($user['role'] === 'student' && ! empty($services)): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Services</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo e($service['title']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900">$<?php echo e(number_format($service['price'], 2)) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php if ($service['status'] === 'active'): ?>
                                            bg-green-100 text-green-800
                                        <?php else: ?>
                                            bg-gray-100 text-gray-800
                                        <?php endif; ?>
                                    ">
                                        <?php echo e(ucfirst($service['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo e(date('M d, Y', strtotime($service['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Order History -->
    <?php if (! empty($orders)): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Order History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">#<?php echo e($order['id']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo e($order['service_title']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo $order['client_id'] == $user['id'] ? 'Client' : 'Student' ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php if ($order['status'] === 'completed'): ?>
                                            bg-green-100 text-green-800
                                        <?php elseif ($order['status'] === 'cancelled'): ?>
                                            bg-red-100 text-red-800
                                        <?php elseif ($order['status'] === 'in_progress'): ?>
                                            bg-blue-100 text-blue-800
                                        <?php else: ?>
                                            bg-yellow-100 text-yellow-800
                                        <?php endif; ?>
                                    ">
                                        <?php echo e(ucfirst(str_replace('_', ' ', $order['status']))) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">$<?php echo e(number_format($order['price'], 2)) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo e(date('M d, Y', strtotime($order['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Review History -->
    <?php if (! empty($reviews)): ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Review History</h2>
            <div class="space-y-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="border-b border-gray-200 pb-4 last:border-0">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-gray-900"><?php echo e($review['service_title']) ?></span>
                                    <span class="text-sm text-gray-500">Order #<?php echo e($review['order_id']) ?></span>
                                </div>
                                <div class="flex items-center mt-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-4 h-4<?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endfor; ?>
                                    <span class="ml-2 text-sm text-gray-600">
                                        <?php echo $review['client_id'] == $user['id'] ? 'Given by this user' : 'Received by this user' ?>
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm text-gray-500"><?php echo e(date('M d, Y', strtotime($review['created_at']))) ?></span>
                        </div>
                        <?php if ($review['comment']): ?>
                            <p class="text-gray-700 text-sm"><?php echo e($review['comment']) ?></p>
                        <?php endif; ?>
                        <?php if ($review['student_reply']): ?>
                            <div class="mt-2 pl-4 border-l-2 border-blue-200">
                                <p class="text-sm text-gray-600"><strong>Student Reply:</strong>                                                                                                 <?php echo e($review['student_reply']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>

<?php include __DIR__ . '/../../layouts/admin.php'; ?>
