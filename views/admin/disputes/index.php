<?php
    require_once __DIR__ . '/../../../src/Helpers.php';
    require_once __DIR__ . '/../../../src/Auth.php';

    $title = 'Disputes Dashboard - Admin';

    ob_start();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Disputes Dashboard</h1>
        
        <!-- Status Filter -->
        <div class="flex items-center space-x-2">
            <label for="status-filter" class="text-sm font-medium text-gray-700">Filter:</label>
            <select
                id="status-filter"
                onchange="window.location.href = '/admin/disputes?status=' + this.value"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="open" <?php echo ($status ?? 'open') === 'open' ? 'selected' : '' ?>>Open Disputes</option>
                <option value="resolved" <?php echo ($status ?? 'open') === 'resolved' ? 'selected' : '' ?>>Resolved Disputes</option>
                <option value="all" <?php echo ($status ?? 'open') === 'all' ? 'selected' : '' ?>>All Disputes</option>
            </select>
        </div>
    </div>

    <?php if (empty($disputes)): ?>
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Disputes Found</h3>
            <p class="text-gray-600">
                <?php if (($status ?? 'open') === 'open'): ?>
                    There are currently no open disputes requiring attention.
                <?php else: ?>
                    No disputes match the selected filter.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Dispute ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order / Service
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Client
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Student
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Opened By
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($disputes as $dispute): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#<?php echo e($dispute['id']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">Order #<?php echo e($dispute['order_id']) ?></div>
                                    <div class="text-sm text-gray-500"><?php echo e($dispute['service_title'] ?? 'N/A') ?></div>
                                    <div class="text-xs text-gray-400">$<?php echo safe_number_format($dispute['order_price'] ?? 0, 2) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo e($dispute['client_name'] ?? explode('@', $dispute['client_email'] ?? '')[0]) ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($dispute['client_email'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo e($dispute['student_name'] ?? explode('@', $dispute['student_email'] ?? '')[0]) ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($dispute['student_email'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php
                                            $openerName = $dispute['opener_name'] ?? explode('@', $dispute['opener_email'] ?? '')[0];
                                            $isClient = $dispute['opened_by'] == $dispute['client_id'];
                                            echo e($openerName);
                                        ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo $isClient ? '(Client)' : '(Student)' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $statusColors = [
                                            'open' => 'bg-yellow-100 text-yellow-800',
                                            'resolved' => 'bg-green-100 text-green-800',
                                        ];
                                        $statusClass = $statusColors[$dispute['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass ?>">
                                        <?php echo ucfirst(e($dispute['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($dispute['created_at'])) ?>
                                    <div class="text-xs text-gray-400"><?php echo date('H:i', strtotime($dispute['created_at'])) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="/admin/disputes/<?php echo e($dispute['id']) ?>" class="text-blue-600 hover:text-blue-900">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?php echo e($status ?? 'open') ?>&page=<?php echo $page - 1 ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?status=<?php echo e($status ?? 'open') ?>&page=<?php echo $page + 1 ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing page <span class="font-medium"><?php echo $page ?></span> of <span class="font-medium"><?php echo $totalPages ?></span>
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?status=<?php echo e($status ?? 'open') ?>&page=<?php echo $page - 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                                            <?php echo $i ?>
                                        </span>
                                    <?php elseif ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                        <a href="?status=<?php echo e($status ?? 'open') ?>&page=<?php echo $i ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <?php echo $i ?>
                                        </a>
                                    <?php elseif (abs($i - $page) == 3): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                            ...
                                        </span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?status=<?php echo e($status ?? 'open') ?>&page=<?php echo $page + 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
