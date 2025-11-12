<?php
    /**
     * Admin Service Detail View
     *
     * @var array $service Service details
     * @var array $student Student details
     * @var array|null $studentProfile Student profile
     * @var array $orders Associated orders
     * @var array $stats Statistics
     * @var bool $canDelete Whether service can be deleted
     */

    $pageTitle = 'Service Details - ' . $service['title'];
    ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="/admin/services" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Services
        </a>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo e($service['title']) ?></h1>
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <span>Service ID: #<?php echo e($service['id']) ?></span>
                    <span>•</span>
                    <span>Created:                                                                                                                                                                                                                                               <?php echo date('M j, Y', strtotime($service['created_at'])) ?></span>
                    <span>•</span>
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
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <?php if ($service['status'] === 'inactive'): ?>
                    <button onclick="showActivateModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Activate
                    </button>
                <?php else: ?>
                    <button onclick="showDeactivateModal()" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                        Deactivate
                    </button>
                <?php endif; ?>

                <?php if ($canDelete): ?>
                    <button onclick="showDeleteModal()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Delete
                    </button>
                <?php else: ?>
                    <button disabled class="px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed" title="Cannot delete service with active orders">
                        Delete
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Service Details -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Service Details</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <p class="text-gray-900"><?php echo e($service['category_name']) ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <p class="text-gray-900 whitespace-pre-wrap"><?php echo e($service['description']) ?></p>
                    </div>

                    <?php if (! empty($service['tags'])): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($service['tags'] as $tag): ?>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                        <?php echo e($tag) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                            <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($service['price'], 2) ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Time</label>
                            <p class="text-2xl font-bold text-gray-900"><?php echo e($service['delivery_days']) ?> days</p>
                        </div>
                    </div>

                    <?php if (! empty($service['sample_files'])): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sample Files</label>
                            <ul class="space-y-2">
                                <?php foreach ($service['sample_files'] as $file): ?>
                                    <?php
                                        $fileName = '';
                                        $filePath = '';

                                        if (is_array($file)) {
                                            $fileName = $file['original_name'] ?? $file['filename'] ?? 'Unknown file';
                                            $filePath = $file['path'] ?? '';
                                        } else {
                                            $fileName = basename($file);
                                            $filePath = $file;
                                        }

                                        $fileUrl = '/storage/file?path=' . urlencode($filePath);
                                    ?>
                                    <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                        <span class="text-gray-900"><?php echo e($fileName) ?></span>
                                        <?php if ($filePath): ?>
                                            <a href="<?php echo e($fileUrl) ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                                View
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Associated Orders -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Associated Orders</h2>

                <?php if (empty($orders)): ?>
                    <p class="text-gray-500">No orders yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm">
                                            <a href="/admin/orders/<?php echo e($order['id']) ?>" class="text-blue-600 hover:text-blue-800">
                                                #<?php echo e($order['id']) ?>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <?php echo e($order['client_name'] ?? $order['client_email']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <?php
                                                $orderStatusColors = [
                                                    'pending'            => 'bg-yellow-100 text-yellow-800',
                                                    'in_progress'        => 'bg-blue-100 text-blue-800',
                                                    'delivered'          => 'bg-purple-100 text-purple-800',
                                                    'revision_requested' => 'bg-orange-100 text-orange-800',
                                                    'completed'          => 'bg-green-100 text-green-800',
                                                    'cancelled'          => 'bg-red-100 text-red-800',
                                                ];
                                                $orderStatusColor = $orderStatusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full<?php echo $orderStatusColor ?>">
                                                <?php echo e(ucfirst(str_replace('_', ' ', $order['status']))) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            $<?php echo number_format($order['price'], 2) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($order['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Statistics -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Statistics</h2>

                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Orders</span>
                        <span class="font-bold text-gray-900"><?php echo $stats['total_orders'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Active Orders</span>
                        <span class="font-bold text-blue-600"><?php echo $stats['active_orders'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Completed</span>
                        <span class="font-bold text-green-600"><?php echo $stats['completed_orders'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Cancelled</span>
                        <span class="font-bold text-red-600"><?php echo $stats['cancelled_orders'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Student Information -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Student Information</h2>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <p class="text-gray-900"><?php echo e($student['name'] ?? $student['email']) ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <p class="text-gray-900"><?php echo e($student['email']) ?></p>
                    </div>

                    <?php if ($studentProfile): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                            <p class="text-gray-900">
                                ⭐                                                                                                                                                                                                                                                      <?php echo number_format($studentProfile['average_rating'], 1) ?>
                                (<?php echo $studentProfile['total_reviews'] ?> reviews)
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Orders</label>
                            <p class="text-gray-900"><?php echo $studentProfile['total_orders'] ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="pt-3">
                        <a href="/admin/users/<?php echo e($student['id']) ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                            View Full Profile →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activate Modal -->
<div id="activateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Activate Service</h3>
        <p class="text-gray-600 mb-4">
            This will activate the service and make it visible to clients. The student will be notified.
        </p>
        <form method="POST" action="/admin/services/<?php echo e($service['id']) ?>/activate">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">

            <div class="flex gap-3">
                <button type="button" onclick="hideActivateModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Activate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Deactivate Modal -->
<div id="deactivateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Deactivate Service</h3>
        <form method="POST" action="/admin/services/<?php echo e($service['id']) ?>/deactivate">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">

            <div class="mb-4">
                <label for="deactivate_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for deactivation
                </label>
                <textarea
                    id="deactivate_reason"
                    name="reason"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                ></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="hideDeactivateModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                    Deactivate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Delete Service</h3>
        <p class="text-gray-600 mb-4">This action cannot be undone. The service and all associated files will be permanently deleted.</p>

        <form method="POST" action="/admin/services/<?php echo e($service['id']) ?>/delete">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">

            <div class="mb-4">
                <label for="delete_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for deletion
                </label>
                <textarea
                    id="delete_reason"
                    name="reason"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                ></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="hideDeleteModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Delete Permanently
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showActivateModal() {
    document.getElementById('activateModal').classList.remove('hidden');
}

function hideActivateModal() {
    document.getElementById('activateModal').classList.add('hidden');
}

function showDeactivateModal() {
    document.getElementById('deactivateModal').classList.remove('hidden');
}

function hideDeactivateModal() {
    document.getElementById('deactivateModal').classList.add('hidden');
}

function showDeleteModal() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modals when clicking outside
document.getElementById('activateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideActivateModal();
    }
});

document.getElementById('deactivateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideDeactivateModal();
    }
});

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideDeleteModal();
    }
});
</script>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
