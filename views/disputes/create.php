<?php
    $pageTitle = 'Open Dispute';
    $user      = Auth::user();

    // Get form errors and data from session
    $errors   = $_SESSION['form_errors'] ?? [];
    $formData = $_SESSION['form_data'] ?? [];
    unset($_SESSION['form_errors'], $_SESSION['form_data']);
?>

<?php include __DIR__ . '/../layouts/dashboard.php'; ?>

<?php ob_start(); ?>

<div class="max-w-3xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Open Dispute</h1>
        <p class="mt-2 text-gray-600">
            If you're experiencing issues with this order that cannot be resolved through normal communication,
            you can open a dispute. An administrator will review your case and make a decision.
        </p>
    </div>

    <!-- Order Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Information</h2>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">Order ID</p>
                <p class="font-medium">#<?php echo e($order['id'])?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Service</p>
                <p class="font-medium"><?php echo e($order['service_title'])?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Status</p>
                <p class="font-medium">
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
                    <span class="px-3 py-1 rounded-full text-sm <?php echo $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800'?>">
                        <?php echo e(ucfirst(str_replace('_', ' ', $order['status'])))?>
                    </span>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Price</p>
                <p class="font-medium">$<?php echo number_format($order['price'], 2)?></p>
            </div>
        </div>
    </div>

    <!-- Dispute Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Dispute Details</h2>

        <form method="POST" action="/disputes/store">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token'] ?? '')?>">
            <input type="hidden" name="order_id" value="<?php echo e($order['id'])?>">

            <!-- Reason -->
            <div class="mb-6">
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for Dispute <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="reason"
                    name="reason"
                    rows="8"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['reason']) ? 'border-red-500' : ''?>"
                    placeholder="Please provide a detailed explanation of the issue. Include specific details about what went wrong and what you expected."
                ><?php echo e($formData['reason'] ?? '')?></textarea>
                <?php if (isset($errors['reason'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($errors['reason'])?></p>
                <?php endif; ?>
                <p class="mt-1 text-sm text-gray-500">Minimum 10 characters. Be as specific as possible.</p>
            </div>

            <!-- Important Notice -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Important</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Opening a dispute will pause the order and notify administrators</li>
                                <li>An admin will review all order details, messages, and files</li>
                                <li>The admin may release payment to the student, refund you, or split the payment</li>
                                <li>This action cannot be undone</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <a href="/orders/<?php echo e($order['id'])?>" class="text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <button
                    type="submit"
                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Open Dispute
                </button>
            </div>
        </form>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../layouts/dashboard.php';
?>
