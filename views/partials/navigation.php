<?php
    // Get user role for navigation
    $userRole = $_SESSION['user_role'] ?? null;
?>
<nav class="fixed top-0 left-0 right-0 z-50 bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Brand -->
            <div class="flex items-center">
                <!-- Mobile menu button -->
                <button
                    data-hamburger
                    @click.prevent="sidebarOpen = !sidebarOpen"
                    class="lg:hidden mr-4 text-gray-600 hover:text-gray-900 focus:outline-none"
                    type="button"
                    aria-label="Toggle sidebar"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <a href="/" class="flex items-center">
                    <span class="text-xl font-bold text-blue-600">Student Skills</span>
                    <span class="text-xl font-bold text-gray-900 ml-1">Marketplace</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-4">
                <?php if (! isset($_SESSION['user_id'])): ?>
                    <!-- Guest Navigation -->
                    <a href="/services" class="text-gray-700 hover:text-blue-600 px-3 py-2">Browse Services</a>
                    <a href="/login" class="text-gray-700 hover:text-blue-600 px-3 py-2">Login</a>
                    <a href="/register" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Sign Up</a>
                <?php else: ?>
                    <!-- Authenticated Navigation -->

                    <!-- Messages -->
                    <div x-data="{ open: false, unreadCount: 0 }" x-init="
                        // Fetch unread count on load
                        fetch('/messages/unread-count')
                            .then(r => r.json())
                            .then(data => { if (data.success) unreadCount = data.count; })
                            .catch(e => console.error('Error fetching unread count:', e));

                        // Poll for updates every 30 seconds
                        setInterval(() => {
                            fetch('/messages/unread-count')
                                .then(r => r.json())
                                .then(data => { if (data.success) unreadCount = data.count; })
                                .catch(e => console.error('Error fetching unread count:', e));
                        }, 30000);
                    " class="relative">
                        <a href="/orders" class="relative p-2 text-gray-600 hover:text-gray-900">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"></span>
                        </a>
                    </div>

                    <!-- Notifications -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="relative p-2 text-gray-600 hover:text-gray-900">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500"></span>
                            <?php endif; ?>
                        </button>

                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-cloak
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg py-2 z-50"
                        >
                            <div class="px-4 py-2 border-b">
                                <h3 class="font-semibold text-gray-900">Notifications</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <a href="/notifications" class="block px-4 py-3 hover:bg-gray-50">
                                    <p class="text-sm text-gray-600">View all notifications</p>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                <?php echo strtoupper(substr($_SESSION['user_email'] ?? 'U', 0, 1)) ?>
                            </div>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-cloak
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50"
                        >
                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                                <p class="text-xs text-gray-500"><?php echo ucfirst($_SESSION['user_role'] ?? '') ?></p>
                            </div>

                            <?php if ($userRole === 'student'): ?>
                                <a href="/student/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                                <a href="/student/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                                <a href="/student/services" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Services</a>
                            <?php elseif ($userRole === 'client'): ?>
                                <a href="/client/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                                <a href="/client/orders" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Orders</a>
                            <?php elseif ($userRole === 'admin'): ?>
                                <a href="/admin/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Admin Dashboard</a>
                            <?php endif; ?>

                            <a href="/settings/account" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                            <div class="border-t my-2"></div>
                            <a href="/auth/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button (for authenticated users) -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="md:hidden">
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="text-gray-600 hover:text-gray-900">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-cloak
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50"
                        >
                            <a href="/notifications" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Notifications</a>
                            <a href="/settings/account" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                            <div class="border-t my-2"></div>
                            <a href="/auth/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
