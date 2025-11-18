<!-- Hero Section -->
<div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
                Connect with Talented Students
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-3xl mx-auto">
                A trusted marketplace where university students showcase their skills and clients find quality services
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/services/search" class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-blue-50 transition">
                    Browse Services
                </a>
                <?php if (! isset($_SESSION['user_id'])): ?>
                    <a href="/auth/register" class="bg-blue-700 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-blue-800 transition border-2 border-white">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Value Proposition Section -->
<div class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Why Choose Student Skills Marketplace?
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                A secure platform designed for the Ethiopian student community
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- For Clients -->
            <div class="text-center p-6 rounded-lg ring-2 ring-blue-200 hover:ring-blue-600 hover:shadow-lg transition-all duration-300">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Find Talent</h3>
                <p class="text-gray-600">
                    Discover skilled students offering services in web development, design, writing, and more
                </p>
            </div>

            <!-- Secure Payments -->
            <div class="text-center p-6 rounded-lg ring-2 ring-green-200 hover:ring-green-600 hover:shadow-lg transition-all duration-300">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Secure Payments</h3>
                <p class="text-gray-600">
                    Your payment is held securely until you're satisfied with the delivered work
                </p>
            </div>

            <!-- For Students -->
            <div class="text-center p-6 rounded-lg ring-2 ring-purple-200 hover:ring-purple-600 hover:shadow-lg transition-all duration-300">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Earn Money</h3>
                <p class="text-gray-600">
                    Monetize your skills and build your portfolio while studying
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Categories Section -->
<?php if (! empty($categories)): ?>
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Browse by Category
            </h2>
            <p class="text-lg text-gray-600">
                Find the perfect service for your needs
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($categories as $category): ?>
                <a href="/services/search?category=<?php echo e($category['id']) ?>"
                   class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition text-center">
                    <h3 class="font-semibold text-gray-900 mb-1">
                        <?php echo e($category['name']) ?>
                    </h3>
                    <?php if (! empty($category['description'])): ?>
                        <p class="text-sm text-gray-600">
                            <?php echo e($category['description']) ?>
                        </p>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Featured Services Section -->
<?php if (! empty($featuredServices)): ?>
<div class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Featured Services
            </h2>
            <p class="text-lg text-gray-600">
                Top-rated services from our talented students
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($featuredServices as $service): ?>
                <a href="/services/<?php echo e($service['id']) ?>"
                   class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-3">
                            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                <?php echo e($service['category_name'] ?? 'Uncategorized') ?>
                            </span>
                            <?php if ($service['average_rating'] > 0): ?>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                    </svg>
                                    <span class="ml-1 text-sm font-semibold text-gray-700">
                                        <?php echo safe_number_format($service['average_rating'], 1) ?>
                                    </span>
                                    <span class="ml-1 text-sm text-gray-500">
                                        (<?php echo e($service['total_reviews']) ?>)
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <?php echo e($service['title']) ?>
                        </h3>

                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                            <?php echo e(substr($service['description'], 0, 120)) ?><?php echo strlen($service['description']) > 120 ? '...' : '' ?>
                        </p>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="text-sm text-gray-500">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <?php echo e($service['delivery_days']) ?> day<?php echo $service['delivery_days'] != 1 ? 's' : '' ?>
                            </div>
                            <div class="text-lg font-bold text-gray-900">
                                $<?php echo safe_number_format($service['price'], 2) ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-8">
            <a href="/services/search" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                View All Services
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Call to Action Section -->
<div class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">
            Ready to Get Started?
        </h2>
        <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_role'] === 'student'): ?>
                    Start offering your services and earn money today
                <?php else: ?>
                    Find talented students to help with your projects
                <?php endif; ?>
            <?php else: ?>
                Join our community of talented students and clients
            <?php endif; ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_role'] === 'student'): ?>
                    <a href="/student/services/create" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
                        Create a Service
                    </a>
                <?php else: ?>
                    <a href="/services/search" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
                        Browse Services
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="/auth/register?role=student" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
                    Sign Up as Student
                </a>
                <a href="/auth/register?role=client" class="bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-800 transition border-2 border-white">
                    Sign Up as Client
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
