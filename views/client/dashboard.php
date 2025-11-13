<!-- Client Dashboard Content -->
<div class="space-y-6">
    <!-- Profile Section -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center space-x-4">
            <!-- Profile Picture -->
            <a href="/client/profile/edit" class="flex-shrink-0">
                <?php if (! empty($user['profile_picture'])): ?>
                    <img src="/storage/file?path=<?php echo urlencode($user['profile_picture']) ?>"
                         alt="Profile Picture"
                         class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 hover:border-blue-500 transition-colors">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold hover:from-blue-600 hover:to-purple-700 transition-colors">
                        <?php echo strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </a>

            <!-- Welcome Message -->
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">
                    Welcome back<?php echo ! empty($user['name']) ? ', ' . htmlspecialchars($user['name']) : '' ?>!
                </h1>
                <p class="text-gray-600">Browse services, manage your orders, and connect with talented students.</p>
                <a href="/client/profile/edit" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-700 mt-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="/services/search" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Browse Services</h3>
                    <p class="text-sm text-gray-600">Find the perfect service</p>
                </div>
            </div>
        </a>

        <a href="/client/orders" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">My Orders</h3>
                    <p class="text-sm text-gray-600">Track your orders</p>
                </div>
            </div>
        </a>

        <a href="/messages" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Messages</h3>
                    <p class="text-sm text-gray-600">Chat with students</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Activity / Orders -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Orders</h2>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No orders yet</h3>
            <p class="mt-2 text-gray-600">Start by browsing services and placing your first order.</p>
            <div class="mt-6">
                <a href="/services/search" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Browse Services
                </a>
            </div>
        </div>
    </div>
</div>
