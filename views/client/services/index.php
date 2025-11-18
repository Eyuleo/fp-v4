<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filter Sidebar -->
            <aside class="w-full lg:w-64 flex-shrink-0" x-data="{ mobileOpen: false }">
                <!-- Mobile Filter Toggle -->
                <button @click="mobileOpen = !mobileOpen" class="lg:hidden w-full bg-white px-4 py-3 rounded-lg shadow-sm flex items-center justify-between mb-4">
                    <span class="font-medium text-gray-900">Filters</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <!-- Filter Panel -->
                <div :class="{ 'hidden': !mobileOpen }" class="lg:block bg-white rounded-lg shadow-sm p-6">
                    <form method="GET" action="/services/search" id="filter-form">
                        <!-- Search Query -->
                        <div class="mb-6">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" id="search" name="q" value="<?php echo e($query ?? '') ?>"
                                   placeholder="Search services..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Category Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo e($category['id']) ?>"<?php echo $selectedCategory == $category['id'] ? 'selected' : '' ?>>
                                        <?php echo e($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" value="<?php echo e($minPrice ?? '') ?>"
                                       placeholder="Min" min="0" step="0.01"
                                       class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="number" name="max_price" value="<?php echo e($maxPrice ?? '') ?>"
                                       placeholder="Max" min="0" step="0.01"
                                       class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Delivery Time Filter -->
                        <div class="mb-6">
                            <label for="max_delivery" class="block text-sm font-medium text-gray-700 mb-2">Max Delivery Days</label>
                            <input type="number" id="max_delivery" name="max_delivery" value="<?php echo e($maxDelivery ?? '') ?>"
                                   placeholder="e.g., 7" min="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Rating Filter -->
                        <div class="mb-6">
                            <label for="min_rating" class="block text-sm font-medium text-gray-700 mb-2">Minimum Rating</label>
                            <select id="min_rating" name="min_rating" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Any Rating</option>
                                <option value="4"                                                                                                                                                                                                                                                      <?php echo($minRating ?? 0) == 4 ? 'selected' : '' ?>>4+ Stars</option>
                                <option value="3"                                                                                                                                                                                                                                                      <?php echo($minRating ?? 0) == 3 ? 'selected' : '' ?>>3+ Stars</option>
                                <option value="2"                                                                                                                                                                                                                                                      <?php echo($minRating ?? 0) == 2 ? 'selected' : '' ?>>2+ Stars</option>
                                <option value="1"                                                                                                                                                                                                                                                      <?php echo($minRating ?? 0) == 1 ? 'selected' : '' ?>>1+ Stars</option>
                            </select>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Apply Filters
                            </button>
                            <a href="/services/search" class="flex-1 text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1">
                <!-- Search Header -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                <?php if (! empty($query)): ?>
                                    Search Results for "<?php echo e($query) ?>"
                                <?php else: ?>
                                    Browse Services
                                <?php endif; ?>
                            </h1>
                            <p class="text-gray-600 mt-1"><?php echo e($total) ?> services found</p>
                        </div>

                        <!-- Sort Dropdown -->
                        <div class="flex items-center gap-2">
                            <label for="sort" class="text-sm text-gray-700">Sort by:</label>
                            <select id="sort" name="sort" onchange="updateSort(this.value)"
                                    class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="relevance"                                                                                                                                                                                                                                                                                              <?php echo $sortBy === 'relevance' ? 'selected' : '' ?>>Relevance</option>
                                <option value="price_asc"                                                                                                                                                                                                                                                                                              <?php echo $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_desc"                                                                                                                                                                                                                                                                                                   <?php echo $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                <option value="rating"                                                                                                                                                                                                                                                                               <?php echo $sortBy === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                                <option value="delivery"                                                                                                                                                                                                                                                                                         <?php echo $sortBy === 'delivery' ? 'selected' : '' ?>>Fastest Delivery</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Service Grid -->
                <?php if (empty($services)): ?>
                    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No services found</h3>
                        <p class="mt-2 text-gray-600">Try adjusting your filters or search query</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($services as $service): ?>
                            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                                <!-- Service Card -->
                                <div class="p-6">
                                    <!-- Student Info -->
                                    <div class="flex items-center mb-4">
                                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                            <?php echo strtoupper(substr($service['student_name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900"><?php echo e($service['student_name'] ?? 'Unknown') ?></p>
                                            <div class="flex items-center">
                                                <span class="text-yellow-400">★</span>
                                                <span class="text-sm text-gray-600 ml-1">
                                                    <?php echo safe_number_format($service['average_rating'] ?? 0, 1) ?>
                                                    (<?php echo e($service['total_reviews'] ?? 0) ?> reviews)
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Service Title & Description -->
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                        <a href="/services/<?php echo e($service['id']) ?>" class="hover:text-blue-600">
                                            <?php echo e($service['title']) ?>
                                        </a>
                                    </h3>
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                        <?php echo e(substr($service['description'], 0, 150)) ?>...
                                    </p>

                                    <!-- Tags -->
                                    <?php if (! empty($service['tags'])): ?>
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <?php
                                                $tags = is_string($service['tags']) ? json_decode($service['tags'], true) : $service['tags'];
                                                if (is_array($tags)):
                                                    foreach (array_slice($tags, 0, 3) as $tag):
                                                ?>
					                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
					                                                    <?php echo e($tag) ?>
					                                                </span>
					                                            <?php
                                                                    endforeach;
                                                                    endif;
                                                                ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Service Meta -->
                                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <?php echo e($service['delivery_days']) ?> day<?php echo $service['delivery_days'] > 1 ? 's' : '' ?>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600">Starting at</p>
                                            <p class="text-xl font-bold text-gray-900">$<?php echo safe_number_format($service['price'], 2) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-8 flex justify-center">
                            <nav class="flex items-center gap-2">
                                <!-- Previous Button -->
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage   = min($totalPages, $page + 2);

                                    for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                       class="px-4 py-2 border rounded-md                                                                                                                                                                                                                                                                                                                                                                              <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                                        <?php echo $i ?>
                                    </a>
                                <?php endfor; ?>

                                <!-- Next Button -->
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    </div>
</div>

<!-- JavaScript for Sort Functionality -->
<script>
    function updateSort(sortValue) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortValue);
        url.searchParams.set('page', '1'); // Reset to first page when sorting
        window.location.href = url.toString();
    }
</script>
