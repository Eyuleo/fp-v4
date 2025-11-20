<?php
    require_once __DIR__ . '/../../src/Helpers.php';
    require_once __DIR__ . '/../../src/Auth.php';

    $title = 'Open Dispute - Student Skills Marketplace';

    ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Open a Dispute</h1>

        <!-- Order Context -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Order Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-sm font-medium text-gray-600">Order ID:</span>
                    <span class="text-sm text-gray-900 ml-2">#<?php echo e($order['id']) ?></span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Service:</span>
                    <span class="text-sm text-gray-900 ml-2"><?php echo e($order['service_title']) ?></span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Price:</span>
                    <span class="text-sm text-gray-900 ml-2">$<?php echo safe_number_format($order['price'], 2) ?></span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Status:</span>
                    <span class="text-sm text-gray-900 ml-2"><?php echo ucfirst(str_replace('_', ' ', e($order['status']))) ?></span>
                </div>
            </div>
        </div>

        <!-- Dispute Information -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Before Opening a Dispute</h3>
                    <ul class="text-sm text-gray-700 space-y-1 list-disc list-inside">
                        <li>Try to resolve the issue directly with the other party through messages</li>
                        <li>Provide clear and detailed information about the problem</li>
                        <li>An administrator will review your case and make a binding decision</li>
                        <li>The decision may result in payment release, refund, or partial refund</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Dispute Form -->
        <form action="/disputes/create" method="POST" data-loading>
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="order_id" value="<?php echo e($order['id']) ?>">

            <div class="mb-6">
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Describe the issue *
                </label>
                <textarea
                    id="reason"
                    name="reason"
                    rows="8"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Please provide a detailed explanation of the issue. Include specific examples and any relevant information that will help the administrator understand your concern..."
                ><?php echo e(old('reason')) ?></textarea>
                <p class="mt-1 text-sm text-gray-500">
                    Be as specific as possible. Include details about what was expected vs. what was delivered, communication issues, or any other relevant information.
                </p>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <a href="/orders/<?php echo e($order['id']) ?>" class="text-gray-600 hover:text-gray-900">
                    ← Back to Order
                </a>
                <button
                    type="submit"
                    class="bg-red-600 text-white px-8 py-3 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 font-medium"
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
