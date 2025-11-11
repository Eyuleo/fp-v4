<?php
    $pageTitle = 'Dispute Management';
    $user      = Auth::user();
?>

<?php include __DIR__ . '/../../layouts/admin.php'; ?>

<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Dispute Management</h1>
    <p class="mt-2 text-gray-600">Review and resolve order disputes</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6 mx-3">
    <form method="GET" action="/admin/disputes" class="flex flex-wrap gap-4">
        <!-- Status Filter -->
        <div class="flex-1 min-w-[200px]">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
                id="status"
                name="status"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                onchange="this.form.submit()"
            >
                <option value="">All Statuses</option>
                <option value="open"                                                                         <?php echo($status ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="resolved"                                                                                 <?php echo($status ?? '') === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>
        </div>

        <!-- Reset Button -->
        <div class="flex items-end">
            <a
                href="/admin/disputes"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
            >
                Reset Filters
            </a>
        </div>
    </form>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 mx-3">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Disputes</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo $totalCount ?></p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Open Disputes</p>
                <p class="text-3xl font-bold text-yellow-600">
                    <?php echo count(array_filter($disputes, fn($d) => $d['status'] === 'open')) ?>
                </p>
            </div>
            <div class="p-3 bg-yellow-100 rounded-full">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Resolved Disputes</p>
                <p class="text-3xl font-bold text-green-600">
                    <?php echo count(array_filter($disputes, fn($d) => $d['status'] === 'resolved')) ?>
                </p>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Disputes Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <?php if (empty($disputes)): ?>
        <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No disputes found</h3>
            <p class="mt-1 text-sm text-gray-500">
                <?php echo $status ? 'Try adjusting your filters' : 'No disputes have been opened yet' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dispute ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Order
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Parties
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Opened By
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Opened Date
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
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
                                <div class="text-sm text-gray-900">
                                    Order #<?php echo e($dispute['order_id']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo e($dispute['service_title']) ?>
                                </div>
                                <div class="text-sm font-medium text-gray-900">
                                    $<?php echo number_format($dispute['order_price'], 2) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <div class="mb-1">
                                        <span class="text-gray-600">Client:</span>
                                        <?php echo e($dispute['client_name'] ?: $dispute['client_email']) ?>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Student:</span>
                                        <?php echo e($dispute['student_name'] ?: $dispute['student_email']) ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo e($dispute['opened_by_name'] ?: $dispute['opened_by_email']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                    $statusColors = [
                                        'open'     => 'bg-yellow-100 text-yellow-800',
                                        'resolved' => 'bg-green-100 text-green-800',
                                    ];
                                ?>
                                <span class="px-3 py-1 rounded-full text-sm font-medium<?php echo $statusColors[$dispute['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?php echo e(ucfirst($dispute['status'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($dispute['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a
                                    href="/admin/disputes/<?php echo e($dispute['id']) ?>"
                                    class="text-blue-600 hover:text-blue-900"
                                >
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
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                            <a
                                href="?page=<?php echo $page - 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?>"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a
                                href="?page=<?php echo $page + 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?>"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing page <span class="font-medium"><?php echo $page ?></span> of
                                <span class="font-medium"><?php echo $totalPages ?></span>
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <?php if ($page > 1): ?>
                                    <a
                                        href="?page=<?php echo $page - 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?>"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                    >
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a
                                        href="?page=<?php echo $i ?><?php echo $status ? '&status=' . urlencode($status) : '' ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium                                                                                                                                                                                                                                                                           <?php echo $i === $page ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50' ?>"
                                    >
                                        <?php echo $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a
                                        href="?page=<?php echo $page + 1 ?><?php echo $status ? '&status=' . urlencode($status) : '' ?>"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                    >
                                        Next
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
