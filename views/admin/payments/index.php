<?php
    /**
     * Admin Payment History View
     */

    $pageTitle = 'Payment History - Admin';
    ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8 flex justify-between items-start">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment History</h1>
            <p class="text-gray-600">View and manage all platform payments</p>
        </div>
        <a href="/admin/payments/export-pdf<?php echo isset($_GET['status']) || isset($_GET['date_from']) || isset($_GET['date_to']) ? '?' . http_build_query(['status' => $_GET['status'] ?? null, 'date_from' => $_GET['date_from'] ?? null, 'date_to' => $_GET['date_to'] ?? null]) : '' ?>" 
           class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export to PDF
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="/admin/payments" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="pending"                                            <?php echo($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="succeeded"                                              <?php echo($status ?? '') === 'succeeded' ? 'selected' : '' ?>>Succeeded</option>
                    <option value="refunded"                                             <?php echo($status ?? '') === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                    <option value="partially_refunded"                                                       <?php echo($status ?? '') === 'partially_refunded' ? 'selected' : '' ?>>Partially Refunded</option>
                    <option value="failed"                                           <?php echo($status ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>

            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo e($dateFrom ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo e($dateTo ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Total Volume</div>
            <div class="text-2xl font-bold text-gray-900">$<?php echo safe_number_format($stats['total_amount'] ?? 0, 2) ?></div>
            <div class="text-xs text-gray-500 mt-1">Successful payments only</div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Total Commission</div>
            <div class="text-2xl font-bold text-green-600">$<?php echo safe_number_format($stats['total_commission'] ?? 0, 2) ?></div>
            <div class="text-xs text-gray-500 mt-1">Platform earnings</div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Total Refunded</div>
            <div class="text-2xl font-bold text-red-600">$<?php echo safe_number_format($stats['total_refunded'] ?? 0, 2) ?></div>
            <div class="text-xs text-gray-500 mt-1">All refunds</div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Successful Payments</div>
            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['succeeded_count'] ?? 0) ?></div>
            <div class="text-xs text-gray-500 mt-1">Completed transactions</div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <?php if (empty($payments)): ?>
            <div class="p-8 text-center text-gray-500">
                <p>No payments found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Payment ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Student
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Commission
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stripe IDs
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($payments as $payment): ?>
                            <?php
                                $statusColors = [
                                    'pending'            => 'bg-yellow-100 text-yellow-800',
                                    'succeeded'          => 'bg-green-100 text-green-800',
                                    'refunded'           => 'bg-red-100 text-red-800',
                                    'partially_refunded' => 'bg-orange-100 text-orange-800',
                                    'failed'             => 'bg-gray-100 text-gray-800',
                                ];
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo e($payment['id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="/orders/<?php echo e($payment['order_id']) ?>" class="text-blue-600 hover:text-blue-800">
                                        Order #<?php echo e($payment['order_id']) ?>
                                    </a>
                                    <div class="text-xs text-gray-500"><?php echo e($payment['service_title']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e($payment['client_email'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e($payment['student_email'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="font-semibold">$<?php echo safe_number_format($payment['amount'], 2) ?></div>
                                    <?php if ($payment['refund_amount'] > 0): ?>
                                        <div class="text-xs text-red-600">
                                            Refunded: $<?php echo safe_number_format($payment['refund_amount'], 2) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    $<?php echo safe_number_format($payment['commission_amount'], 2) ?>
                                    <div class="text-xs text-gray-500">
                                        Student: $<?php echo safe_number_format($payment['student_amount'], 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full                                                                                                                    <?php echo $statusColors[$payment['status']] ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', e($payment['status']))) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php if ($payment['stripe_payment_intent_id']): ?>
                                        <div class="text-xs mb-1">
                                            <span class="font-medium">PI:</span>
                                            <span class="font-mono"><?php echo e(substr($payment['stripe_payment_intent_id'], 0, 20)) ?>...</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($payment['stripe_checkout_session_id']): ?>
                                        <div class="text-xs mb-1">
                                            <span class="font-medium">CS:</span>
                                            <span class="font-mono"><?php echo e(substr($payment['stripe_checkout_session_id'], 0, 20)) ?>...</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($payment['stripe_transfer_id']): ?>
                                        <div class="text-xs">
                                            <span class="font-medium">TR:</span>
                                            <span class="font-mono"><?php echo e(substr($payment['stripe_transfer_id'], 0, 20)) ?>...</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($payment['created_at'])) ?>
                                    <div class="text-xs"><?php echo date('H:i:s', strtotime($payment['created_at'])) ?></div>
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
                            Showing page                                         <?php echo $page ?> of<?php echo $totalPages ?> (<?php echo $totalCount ?> total payments)
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : '' ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : '' ?>"
                                   class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : '' ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : '' ?>"
                                   class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
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
