<?php
    $pageTitle = 'Create Service';
    $errors    = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Create New Service</h1>
        <p class="mt-2 text-gray-600">List your skills and services to start earning</p>
    </div>

    <?php require __DIR__ . '/../../partials/alert.php'; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="/student/services/store" method="POST" enctype="multipart/form-data" data-loading>
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
                    value="<?php echo e(old('title'))?>"
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
                        <option value="<?php echo e($category['id'])?>" <?php echo old('category_id') == $category['id'] ? 'selected' : ''?>>
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
                ><?php echo e(old('description'))?></textarea>
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
                    value="<?php echo e(old('tags'))?>"
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
                            value="<?php echo e(old('price'))?>"
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
                        value="<?php echo e(old('delivery_days'))?>"
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

            <!-- Sample Files -->
            <div class="mb-6">
                <label for="sample_files" class="block text-sm font-medium text-gray-700 mb-2">
                    Sample Work (Optional)
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

            <!-- Info Box -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Service will be created as inactive</h3>
                        <p class="mt-1 text-sm text-blue-700">After creating your service, you can activate it to make it visible to clients.</p>
                    </div>
                </div>
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
                    Create Service
                </button>
            </div>
        </form>
    </div>
</div>
