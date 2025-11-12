<footer class="bg-gray-900 text-gray-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand Section -->
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center mb-4">
                    <span class="text-xl font-bold text-white">Student Skills</span>
                    <span class="text-xl font-bold text-blue-400 ml-1">Marketplace</span>
                </div>
                <p class="text-gray-400 mb-4">
                    Connecting Ethiopian university students with clients seeking quality services.
                    A trusted platform for skill monetization and project collaboration.
                </p>
                <div class="flex space-x-4">
                    <!-- Social Media Links (placeholder) -->
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-white font-semibold mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="/" class="hover:text-white transition">Home</a>
                    </li>
                    <li>
                        <a href="/services/search" class="hover:text-white transition">Browse Services</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_role'] === 'student'): ?>
                            <li>
                                <a href="/student/dashboard" class="hover:text-white transition">Dashboard</a>
                            </li>
                            <li>
                                <a href="/student/services" class="hover:text-white transition">My Services</a>
                            </li>
                        <?php elseif ($_SESSION['user_role'] === 'client'): ?>
                            <li>
                                <a href="/client/dashboard" class="hover:text-white transition">Dashboard</a>
                            </li>
                            <li>
                                <a href="/orders" class="hover:text-white transition">My Orders</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li>
                            <a href="/auth/login" class="hover:text-white transition">Login</a>
                        </li>
                        <li>
                            <a href="/auth/register" class="hover:text-white transition">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <h3 class="text-white font-semibold mb-4">Support</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="#" class="hover:text-white transition">About Us</a>
                    </li>
                    <li>
                        <a href="#" class="hover:text-white transition">Contact</a>
                    </li>
                    <li>
                        <a href="#" class="hover:text-white transition">Help Center</a>
                    </li>
                    <li>
                        <a href="#" class="hover:text-white transition">Terms of Service</a>
                    </li>
                    <li>
                        <a href="#" class="hover:text-white transition">Privacy Policy</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
            <p class="text-gray-400 text-sm">
                ©                   <?php echo date('Y') ?> Student Skills Marketplace. All rights reserved.
            </p>
            <p class="text-gray-400 text-sm mt-4 md:mt-0">
                Made with ❤️ for Ethiopian students
            </p>
        </div>
    </div>
</footer>
