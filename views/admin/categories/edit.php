<?php
    /**
     * Admin Edit Category View
     */

    $pageTitle = 'Edit Category';
    ob_start();
?>

<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="mb-6 mx-3">
        <div class="flex items-center mb-4">
            <a href="/admin/categories" class="text-blue-600 hover:text-blue-800 mr-4">
                ← Back to Categories
            </a>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">Edit Category</h1>
        <p class="mt-2 text-gray-600">Update category information</p>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 mx-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <?php echo e($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['form_errors'])): ?>
        <div class="mb-6 mx-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <p class="font-semibold mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside">
                <?php foreach ($_SESSION['form_errors'] as $error): ?>
                    <li><?php echo e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['form_errors']); ?>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="/admin/categories/<?php echo e($category['id']) ?>/update">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token'] ?? '') ?>">

            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Category Name *
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    maxlength="100"
                    value="<?php echo e($_SESSION['old']['name'] ?? $category['name']) ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="e.g., Web Development"
                >
                <p class="mt-1 text-sm text-gray-500">
                    The display name for this category
                </p>
            </div>

            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description (Optional)
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Brief description of this category"
                ><?php echo e($_SESSION['old']['description'] ?? $category['description'] ?? '') ?></textarea>
                <p class="mt-1 text-sm text-gray-500">
                    Optional description to help users understand this category
                </p>
            </div>

            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Current Slug</h3>
                <p class="text-sm text-gray-600">
                    <code class="bg-gray-200 px-2 py-1 rounded"><?php echo e($category['slug']) ?></code>
                </p>
                <p class="mt-2 text-xs text-gray-500">
                    The slug will be automatically updated based on the category name
                </p>
            </div>

            <div class="flex justify-end space-x-3">
                <a
                    href="/admin/categories"
                    class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Update Category
                </button>
            </div>
        </form>
    </div>

    <!-- Info Box -->
    <div class="mt-6 mx-3 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-blue-900 mb-2">Important Notes:</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• Updating the category name will automatically generate a new slug if needed</li>
            <li>• All services in this category will remain associated after the update</li>
            <li>• Category changes are logged in the audit trail</li>
        </ul>
    </div>
</div>

<?php
    // Clear old form data
    unset($_SESSION['old']);

    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin.php';
?>
