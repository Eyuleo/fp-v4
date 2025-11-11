<?php
    /**
     * Admin User Management View
     */

    $pageTitle = 'User Management';
?>

<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
        <p class="mt-2 text-gray-600">Manage all users on the platform</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="/admin/users" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search by Email</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="<?php echo e($_GET['search'] ?? '') ?>"
                        placeholder="Enter email..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <!-- Role Filter -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select
                        id="role"
                        name="role"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Roles</option>
                        <option value="student"                                                                                               <?php echo($_GET['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="client"                                                                                             <?php echo($_GET['role'] ?? '') === 'client' ? 'selected' : '' ?>>Client</option>
                        <option value="admin"                                                                                           <?php echo($_GET['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="unverified"                                                                                                     <?php echo($_GET['status'] ?? '') === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                        <option value="active"                                                                                             <?php echo($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended"                                                                                                   <?php echo($_GET['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        Apply Filters
                    </button>
                </div>
            </div>

            <!-- Clear Filters -->
            <?php if (! empty($_GET['search']) || ! empty($_GET['role']) || ! empty($_GET['status'])): ?>
                <div>
                    <a href="/admin/users" class="text-sm text-blue-600 hover:text-blue-800">Clear all filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Results Summary -->
    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Showing                                       <?php echo count($users) ?> of<?php echo $totalCount ?> users
        </p>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No users found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo e($u['id']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo e($u['email']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php if ($u['role'] === 'admin'): ?>
                                        bg-purple-100 text-purple-800
                                    <?php elseif ($u['role'] === 'student'): ?>
                                        bg-blue-100 text-blue-800
                                    <?php else: ?>
                                        bg-green-100 text-green-800
                                    <?php endif; ?>
                                ">
                                    <?php echo e(ucfirst($u['role'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php if ($u['status'] === 'active'): ?>
                                        bg-green-100 text-green-800
                                    <?php elseif ($u['status'] === 'suspended'): ?>
                                        bg-red-100 text-red-800
                                    <?php else: ?>
                                        bg-yellow-100 text-yellow-800
                                    <?php endif; ?>
                                ">
                                    <?php echo e(ucfirst($u['status'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo e(date('M d, Y', strtotime($u['created_at']))) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="/admin/users/<?php echo e($u['id']) ?>" class="text-blue-600 hover:text-blue-900">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <!-- Previous Page -->
                <?php if ($page > 1): ?>
                    <a
                        href="?page=<?php echo $page - 1 ?><?php echo ! empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?php echo ! empty($_GET['role']) ? '&role=' . urlencode($_GET['role']) : '' ?><?php echo ! empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '' ?>"
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                    >
                        Previous
                    </a>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                            <?php echo $i ?>
                        </span>
                    <?php elseif ($i === 1 || $i === $totalPages || abs($i - $page) <= 2): ?>
                        <a
                            href="?page=<?php echo $i ?><?php echo ! empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?php echo ! empty($_GET['role']) ? '&role=' . urlencode($_GET['role']) : '' ?><?php echo ! empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '' ?>"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            <?php echo $i ?>
                        </a>
                    <?php elseif (abs($i - $page) === 3): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                            ...
                        </span>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Next Page -->
                <?php if ($page < $totalPages): ?>
                    <a
                        href="?page=<?php echo $page + 1 ?><?php echo ! empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?php echo ! empty($_GET['role']) ? '&role=' . urlencode($_GET['role']) : '' ?><?php echo ! empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '' ?>"
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                    >
                        Next
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>

<?php include __DIR__ . '/../../layouts/admin.php'; ?>
