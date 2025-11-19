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
                    Connecting students with clients seeking quality services.
                    A trusted platform for skill monetization and project collaboration.
                </p>
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
            <!-- <div>
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
            </div> -->
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-center items-center">
            <p class="text-gray-400 text-sm">
                ©                   <?php echo date('Y') ?> Student Skills Marketplace. All rights reserved.
            </p>
            
        </div>
    </div>
</footer>
