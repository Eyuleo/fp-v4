<?php
    /**
     * Admin Platform Settings View
     */

    $pageTitle = 'Platform Settings';
    ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Platform Settings</h1>
        <p class="mt-2 text-gray-600">Configure platform-wide settings and parameters</p>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <?php echo e($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <?php echo e($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['form_errors'])): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <p class="font-semibold mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside">
                <?php foreach ($_SESSION['form_errors'] as $error): ?>
                    <li><?php echo e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['form_errors']); ?>
    <?php endif; ?>

    <!-- Settings Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="/admin/settings/update">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token'] ?? '') ?>">

            <!-- General Settings Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">General Settings</h2>

                <!-- Platform Name -->
                <div class="mb-6">
                    <label for="platform_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Platform Name
                    </label>
                    <input
                        type="text"
                        id="platform_name"
                        name="platform_name"
                        value="<?php echo e($platformName) ?>"
                        maxlength="100"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="mt-2 text-sm text-gray-500">
                        The name of your platform displayed throughout the site
                    </p>
                </div>

                <!-- Support Email -->
                <div class="mb-6">
                    <label for="support_email" class="block text-sm font-medium text-gray-700 mb-2">
                        Support Email
                    </label>
                    <input
                        type="email"
                        id="support_email"
                        name="support_email"
                        value="<?php echo e($supportEmail) ?>"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="mt-2 text-sm text-gray-500">
                        Email address for customer support inquiries
                    </p>
                </div>
            </div>

            <!-- Payment Settings Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Payment Settings</h2>

                <!-- Commission Rate -->
                <div class="mb-6">
                    <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">
                        Commission Rate (%)
                    </label>
                    <input
                        type="number"
                        id="commission_rate"
                        name="commission_rate"
                        value="<?php echo e($commissionRate) ?>"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="mt-2 text-sm text-gray-500">
                        Percentage of order value deducted as platform commission (0-100)
                    </p>
                </div>

                <!-- Min Order Price -->
                <div class="mb-6">
                    <label for="min_order_price" class="block text-sm font-medium text-gray-700 mb-2">
                        Minimum Order Price ($)
                    </label>
                    <input
                        type="number"
                        id="min_order_price"
                        name="min_order_price"
                        value="<?php echo e($minOrderPrice) ?>"
                        min="0"
                        step="0.01"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="mt-2 text-sm text-gray-500">
                        Minimum price allowed for service orders
                    </p>
                </div>

                <!-- Max Order Price -->
                <div class="mb-6">
                    <label for="max_order_price" class="block text-sm font-medium text-gray-700 mb-2">
                        Maximum Order Price ($)
                    </label>
                    <input
                        type="number"
                        id="max_order_price"
                        name="max_order_price"
                        value="<?php echo e($maxOrderPrice) ?>"
                        min="1"
                        step="0.01"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="mt-2 text-sm text-gray-500">
                        Maximum price allowed for service orders
                    </p>
                </div>
            </div>

            <!-- Order Settings Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Order Settings</h2>

                <!-- Max Revisions -->
                <div class="mb-6">
                    <label for="max_revisions" class="block text-sm font-medium text-gray-700 mb-2">
                        Maximum Revisions
                    </label>
                    <input
                        type="number"
                        id="max_revisions"
                        name="max_revisions"
                        value="<?php echo e($maxRevisions) ?>"
                        min="1"
                        max="10"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="mt-2 text-sm text-gray-500">
                        Maximum number of revisions allowed per order (must be greater than 0)
                    </p>
                </div>

                <!-- Order Delivery Buffer -->
                <div class="mb-6">
                    <label for="order_delivery_buffer" class="block text-sm font-medium text-gray-700 mb-2">
                        Order Delivery Buffer (hours)
                    </label>
                    <input
                        type="number"
                        id="order_delivery_buffer"
                        name="order_delivery_buffer"
                        value="<?php echo e($orderDeliveryBuffer) ?>"
                        min="0"
                        max="168"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="mt-2 text-sm text-gray-500">
                        Additional time buffer (in hours) added to delivery deadlines for student flexibility
                    </p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Save All Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Current Settings Info -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-blue-900 mb-2">Important Notes:</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• All setting changes apply to new orders placed after the update</li>
            <li>• Existing orders will retain their original settings</li>
            <li>• Price limits affect service creation and order placement</li>
            <li>• Delivery buffer provides students extra time beyond the stated deadline</li>
            <li>• All changes are logged in the audit trail for compliance</li>
        </ul>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../layouts/admin.php';
?>
