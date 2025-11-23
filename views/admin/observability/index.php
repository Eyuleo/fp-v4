<?php
    /**
     * Admin Observability Dashboard View
     */

    $pageTitle = 'Observability Dashboard - Admin';
    ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Observability Dashboard</h1>
        <p class="text-gray-600">Monitor payment events, webhook processing, and balance updates</p>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Total Audit Logs</div>
            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_audit_logs'] ?? 0) ?></div>
            <div class="text-xs text-gray-500 mt-1">All system events</div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Processed Webhooks</div>
            <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['processed_webhooks'] ?? 0) ?></div>
            <div class="text-xs text-gray-500 mt-1">Successfully processed</div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Pending Webhooks</div>
            <div class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['unprocessed_webhooks'] ?? 0) ?></div>
            <div class="text-xs text-gray-500 mt-1">Awaiting processing</div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-sm text-gray-600 mb-1">Webhook Errors</div>
            <div class="text-2xl font-bold text-red-600"><?php echo number_format($stats['webhook_errors'] ?? 0) ?></div>
            <div class="text-xs text-gray-500 mt-1">Failed processing</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="/admin/observability" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label for="event_type" class="block text-sm font-medium text-gray-700 mb-2">Event Type</label>
                <select name="event_type" id="event_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Events</option>
                    <option value="payment_created" <?php echo($eventType ?? '') === 'payment_created' ? 'selected' : '' ?>>Payment Created</option>
                    <option value="payment_confirmed" <?php echo($eventType ?? '') === 'payment_confirmed' ? 'selected' : '' ?>>Payment Confirmed</option>
                    <option value="balance_updated" <?php echo($eventType ?? '') === 'balance_updated' ? 'selected' : '' ?>>Balance Updated</option>
                    <option value="order_created" <?php echo($eventType ?? '') === 'order_created' ? 'selected' : '' ?>>Order Created</option>
                    <option value="webhook_processed" <?php echo($eventType ?? '') === 'webhook_processed' ? 'selected' : '' ?>>Webhook Processed</option>
                </select>
            </div>

            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">User</label>
                <select name="user_id" id="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo e($u['id']) ?>" <?php echo($userId ?? '') == $u['id'] ? 'selected' : '' ?>>
                            <?php echo e($u['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Webhook Status</label>
                <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="processed" <?php echo($status ?? '') === 'processed' ? 'selected' : '' ?>>Processed</option>
                    <option value="unprocessed" <?php echo($status ?? '') === 'unprocessed' ? 'selected' : '' ?>>Unprocessed</option>
                    <option value="error" <?php echo($status ?? '') === 'error' ? 'selected' : '' ?>>Error</option>
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

            <div class="md:col-span-5 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Tabs for Audit Logs and Webhooks -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button onclick="showTab('audit')" id="audit-tab" class="tab-button active px-6 py-3 text-sm font-medium border-b-2 border-blue-500 text-blue-600">
                    Audit Logs
                </button>
                <button onclick="showTab('webhooks')" id="webhooks-tab" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Webhook Events
                </button>
            </nav>
        </div>

        <!-- Audit Logs Tab -->
        <div id="audit-content" class="tab-content">
            <?php if (empty($auditLogs)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p>No audit logs found matching your criteria.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Timestamp
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    User
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Resource
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Details
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    IP Address
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($auditLogs as $log): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo e(str_replace('_', ' ', ucfirst($log['action']))) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($log['user_email']): ?>
                                            <?php echo e($log['user_name'] ?? explode('@', $log['user_email'])[0]) ?>
                                            <div class="text-xs text-gray-500"><?php echo e($log['user_email']) ?></div>
                                        <?php else: ?>
                                            <span class="text-gray-400">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo e(ucfirst($log['resource_type'])) ?> #<?php echo e($log['resource_id']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php if ($log['old_values']): ?>
                                            <div class="mb-1">
                                                <span class="font-medium">Old:</span>
                                                <code class="text-xs bg-gray-100 px-1 py-0.5 rounded"><?php echo e(substr($log['old_values'], 0, 50)) ?><?php echo strlen($log['old_values']) > 50 ? '...' : '' ?></code>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($log['new_values']): ?>
                                            <div>
                                                <span class="font-medium">New:</span>
                                                <code class="text-xs bg-gray-100 px-1 py-0.5 rounded"><?php echo e(substr($log['new_values'], 0, 50)) ?><?php echo strlen($log['new_values']) > 50 ? '...' : '' ?></code>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo e($log['ip_address'] ?? 'N/A') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Webhooks Tab -->
        <div id="webhooks-content" class="tab-content hidden">
            <?php if (empty($webhooks)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p>No webhook events found matching your criteria.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Timestamp
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Event Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stripe Event ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Processed At
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Error
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($webhooks as $webhook): ?>
                                <?php
                                    $statusColor = 'bg-gray-100 text-gray-800';
                                    if ($webhook['processed']) {
                                        $statusColor = 'bg-green-100 text-green-800';
                                    } elseif ($webhook['error']) {
                                        $statusColor = 'bg-red-100 text-red-800';
                                    } else {
                                        $statusColor = 'bg-yellow-100 text-yellow-800';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y H:i:s', strtotime($webhook['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?php echo e($webhook['event_type']) ?></code>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <span class="font-mono text-xs"><?php echo e(substr($webhook['stripe_event_id'], 0, 30)) ?>...</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor ?>">
                                            <?php if ($webhook['processed']): ?>
                                                Processed
                                            <?php elseif ($webhook['error']): ?>
                                                Error
                                            <?php else: ?>
                                                Pending
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $webhook['processed_at'] ? date('M d, Y H:i:s', strtotime($webhook['processed_at'])) : 'N/A' ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php if ($webhook['error']): ?>
                                            <span class="text-red-600 text-xs"><?php echo e(substr($webhook['error'], 0, 100)) ?><?php echo strlen($webhook['error']) > 100 ? '...' : '' ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-400">None</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing page <?php echo $page ?> of <?php echo $totalPages ?>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1 ?><?php echo $eventType ? '&event_type=' . urlencode($eventType) : '' ?><?php echo $userId ? '&user_id=' . urlencode($userId) : '' ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : '' ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : '' ?>"
                               class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1 ?><?php echo $eventType ? '&event_type=' . urlencode($eventType) : '' ?><?php echo $userId ? '&user_id=' . urlencode($userId) : '' ?><?php echo $status ? '&status=' . urlencode($status) : '' ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : '' ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : '' ?>"
                               class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-content').classList.remove('hidden');
    
    // Add active class to selected tab
    const activeTab = document.getElementById(tabName + '-tab');
    activeTab.classList.add('active', 'border-blue-500', 'text-blue-600');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
}
</script>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
