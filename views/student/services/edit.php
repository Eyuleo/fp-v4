<?php
    $pageTitle = 'Edit Service';
    $errors    = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);

    // Convert tags array to comma-separated string for display
    $tagsString = is_array($service['tags']) ? implode(', ', $service['tags']) : '';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Edit Service</h1>
        <p class="mt-2 text-gray-600">Update your service details</p>
    </div>

    <?php require __DIR__ . '/../../partials/alert.php'; ?>

    <?php if ($service['status'] === 'rejected' && !empty($service['rejection_reason'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-6 mb-6 rounded-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-red-800 mb-2">
                        Service Rejected - Please Address the Following Issues
                    </h3>
                    <div class="text-sm text-red-700 mb-4">
                        <p class="font-medium mb-2">Reason for rejection:</p>
                        <p class="whitespace-pre-wrap bg-white p-3 rounded border border-red-200"><?php echo e($service['rejection_reason']) ?></p>
                    </div>
                    <div class="bg-white p-4 rounded border border-red-200">
                        <p class="text-sm font-semibold text-red-800 mb-2">✅ Next Steps:</p>
                        <ol class="text-sm text-red-700 space-y-1 list-decimal list-inside">
                            <li>Review the feedback above carefully</li>
                            <li>Make the necessary changes to your service below</li>
                            <li>Click "Update Service" to save and automatically resubmit for review</li>
                            <li>Our team will review your updated service and notify you of the outcome</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($hasActiveOrders && !empty($activeOrders)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        Core Fields Restricted Due to Active Orders
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p class="mb-2">This service has <?php echo count($activeOrders); ?> active <?php echo count($activeOrders) === 1 ? 'order' : 'orders'; ?>. Core service details (price, delivery time, description, category) cannot be changed until these orders are completed:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <?php foreach ($activeOrders as $order): ?>
                                <li>
                                    Order #<?php echo e($order['id']); ?> - 
                                    <span class="font-medium"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span>
                                    <?php if ($order['is_overdue']): ?>
                                        <span class="text-red-600 font-semibold">(Overdue)</span>
                                    <?php endif; ?>
                                    - Client: <?php echo e($order['client_name']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mt-2 text-xs">You can still edit the title, tags, and add sample files.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="/student/services/<?php echo e($service['id'])?>/update" method="POST" enctype="multipart/form-data" data-loading>
            <?php echo csrf_field()?>

            <!-- Title -->
            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Service Title <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?php echo e(old('title', $service['title']))?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['title']) ? 'border-red-500' : ''?>"
                    placeholder="e.g., I will design a professional logo for your business"
                    required
                >
                <?php if (isset($errors['title'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($errors['title'])?></p>
                <?php endif; ?>
            </div>

            <!-- Category -->
            <div class="mb-6">
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Category <span class="text-red-500">*</span>
                    <?php if ($hasActiveOrders): ?>
                        <span class="text-xs text-yellow-600">(Locked - Active Orders)</span>
                    <?php endif; ?>
                </label>
                <select
                    id="category_id"
                    name="category_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['category_id']) ? 'border-red-500' : ''?> <?php echo $hasActiveOrders ? 'bg-gray-100 cursor-not-allowed' : ''?>"
                    <?php echo $hasActiveOrders ? 'disabled' : 'required'?>
                >
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo e($category['id'])?>" <?php echo old('category_id', $service['category_id']) == $category['id'] ? 'selected' : ''?>>
                            <?php echo e($category['name'])?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($errors['category_id'])?></p>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description <span class="text-red-500">*</span>
                    <?php if ($hasActiveOrders): ?>
                        <span class="text-xs text-yellow-600">(Locked - Active Orders)</span>
                    <?php endif; ?>
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['description']) ? 'border-red-500' : ''?> <?php echo $hasActiveOrders ? 'bg-gray-100 cursor-not-allowed' : ''?>"
                    placeholder="Describe your service in detail. What will you deliver? What makes your service unique?"
                    <?php echo $hasActiveOrders ? 'disabled' : 'required'?>
                ><?php echo e(old('description', $service['description']))?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($errors['description'])?></p>
                <?php endif; ?>
                <?php if (!$hasActiveOrders): ?>
                    <p class="mt-1 text-sm text-gray-500">Minimum 20 characters</p>
                <?php endif; ?>
            </div>

            <!-- Tags -->
            <div class="mb-6">
                <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">
                    Tags (Optional)
                </label>
                <input
                    type="text"
                    id="tags"
                    name="tags"
                    value="<?php echo e(old('tags', $tagsString))?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['tags']) ? 'border-red-500' : ''?>"
                    placeholder="e.g., logo design, branding, minimalist (comma-separated)"
                >
                <?php if (isset($errors['tags'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($errors['tags'])?></p>
                <?php endif; ?>
                <p class="mt-1 text-sm text-gray-500">Add up to 10 tags, separated by commas</p>
            </div>

            <!-- Price and Delivery Days -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                        Price (USD) <span class="text-red-500">*</span>
                        <?php if ($hasActiveOrders): ?>
                            <span class="text-xs text-yellow-600">(Locked - Active Orders)</span>
                        <?php endif; ?>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-2 text-gray-500">$</span>
                        <input
                            type="number"
                            id="price"
                            name="price"
                            value="<?php echo e(old('price', $service['price']))?>"
                            step="0.01"
                            min="0.01"
                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['price']) ? 'border-red-500' : ''?> <?php echo $hasActiveOrders ? 'bg-gray-100 cursor-not-allowed' : ''?>"
                            placeholder="0.00"
                            <?php echo $hasActiveOrders ? 'disabled' : 'required'?>
                        >
                    </div>
                    <?php if (isset($errors['price'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo e($errors['price'])?></p>
                    <?php endif; ?>
                </div>

                <!-- Delivery Days -->
                <div>
                    <label for="delivery_days" class="block text-sm font-medium text-gray-700 mb-2">
                        Delivery Time (Days) <span class="text-red-500">*</span>
                        <?php if ($hasActiveOrders): ?>
                            <span class="text-xs text-yellow-600">(Locked - Active Orders)</span>
                        <?php endif; ?>
                    </label>
                    <input
                        type="number"
                        id="delivery_days"
                        name="delivery_days"
                        value="<?php echo e(old('delivery_days', $service['delivery_days']))?>"
                        min="1"
                        max="365"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['delivery_days']) ? 'border-red-500' : ''?> <?php echo $hasActiveOrders ? 'bg-gray-100 cursor-not-allowed' : ''?>"
                        placeholder="e.g., 3"
                        <?php echo $hasActiveOrders ? 'disabled' : 'required'?>
                    >
                    <?php if (isset($errors['delivery_days'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo e($errors['delivery_days'])?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Existing Sample Files -->
            <?php if (! empty($service['sample_files'])): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Sample Files
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php foreach ($service['sample_files'] as $file): ?>
                            <div class="border border-gray-300 rounded-lg p-3">
                                <div class="text-sm text-gray-600 truncate" title="<?php echo e($file['original_name'])?>">
                                    <?php echo e($file['original_name'])?>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?php echo safe_number_format($file['size'] / 1024, 1)?> KB
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Add New Sample Files -->
            <div class="mb-6">
                <label for="sample_files" class="block text-sm font-medium text-gray-700 mb-2">
                    Add More Sample Files (Optional)
                </label>
                <input
                    type="file"
                    id="sample_files"
                    name="sample_files[]"
                    multiple
                    accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['sample_files']) ? 'border-red-500' : ''?>"
                >
                <?php if (isset($errors['sample_files'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($errors['sample_files'])?></p>
                <?php endif; ?>
                <p class="mt-1 text-sm text-gray-500">Upload up to 5 files. Max 10MB per file. Allowed: JPG, PNG, GIF, PDF, DOC, DOCX, ZIP</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-4">
                <a href="/student/services" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    Update Service
                </button>
            </div>
        </form>
    </div>
</div>
