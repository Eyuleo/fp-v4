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

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="/student/services/<?php echo e($service['id'])?>/update" method="POST" enctype="multipart/form-data">
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
                </label>
                <select
                    id="category_id"
                    name="category_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['category_id']) ? 'border-red-500' : ''?>"
                    required
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
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['description']) ? 'border-red-500' : ''?>"
                    placeholder="Describe your service in detail. What will you deliver? What makes your service unique?"
                    required
                ><?php echo e(old('description', $service['description']))?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($errors['description'])?></p>
                <?php endif; ?>
                <p class="mt-1 text-sm text-gray-500">Minimum 20 characters</p>
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
                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['price']) ? 'border-red-500' : ''?>"
                            placeholder="0.00"
                            required
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
                    </label>
                    <input
                        type="number"
                        id="delivery_days"
                        name="delivery_days"
                        value="<?php echo e(old('delivery_days', $service['delivery_days']))?>"
                        min="1"
                        max="365"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['delivery_days']) ? 'border-red-500' : ''?>"
                        placeholder="e.g., 3"
                        required
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
                                    <?php echo number_format($file['size'] / 1024, 1)?> KB
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
