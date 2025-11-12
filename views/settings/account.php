<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Account Settings</h1>
        <p class="text-gray-600 mt-2">Manage your account information and security</p>
    </div>

    <?php if (isset($_SESSION['errors']['general'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo e($_SESSION['errors']['general']) ?>
        </div>
    <?php endif; ?>

    <div class="space-y-6">
        <!-- Account Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Account Information</h2>

            <form action="/settings/account/update" method="POST" class="space-y-4">
                <?php echo csrf_field() ?>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Name
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          <?php echo isset($_SESSION['errors']['name']) ? 'border-red-500' : '' ?>"
                        placeholder="Your full name"
                        value="<?php echo e(old('name', $user['name'] ?? '')) ?>"
                    >
                    <?php if (isset($_SESSION['errors']['name'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['name']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          <?php echo isset($_SESSION['errors']['email']) ? 'border-red-500' : '' ?>"
                        placeholder="your@email.com"
                        value="<?php echo e(old('email', $user['email'] ?? '')) ?>"
                        required
                    >
                    <?php if (isset($_SESSION['errors']['email'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['email']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Role (Read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Account Type
                    </label>
                    <input
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50"
                        value="<?php echo e(ucfirst($user['role'] ?? '')) ?>"
                        disabled
                    >
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Change Password</h2>

            <form action="/settings/password/update" method="POST" class="space-y-4">
                <?php echo csrf_field() ?>

                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Password
                    </label>
                    <div class="password-input-wrapper relative">
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                                                              <?php echo isset($_SESSION['errors']['current_password']) ? 'border-red-500' : '' ?>"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle-btn absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                            aria-label="Show password"
                        >
                            <!-- Eye Icon (Show) -->
                            <svg class="eye-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <!-- Eye Slash Icon (Hide) -->
                            <svg class="eye-slash-icon w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($_SESSION['errors']['current_password'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['current_password']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- New Password -->
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>
                    <div class="password-input-wrapper relative">
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                               <?php echo isset($_SESSION['errors']['new_password']) ? 'border-red-500' : '' ?>"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle-btn absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                            aria-label="Show password"
                        >
                            <!-- Eye Icon (Show) -->
                            <svg class="eye-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <!-- Eye Slash Icon (Hide) -->
                            <svg class="eye-slash-icon w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($_SESSION['errors']['new_password'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['new_password']) ?></p>
                    <?php endif; ?>
                    <p class="text-gray-500 text-sm mt-1">Must be at least 8 characters</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm New Password
                    </label>
                    <div class="password-input-wrapper relative">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             <?php echo isset($_SESSION['errors']['confirm_password']) ? 'border-red-500' : '' ?>"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle-btn absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                            aria-label="Show password"
                        >
                            <!-- Eye Icon (Show) -->
                            <svg class="eye-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <!-- Eye Slash Icon (Hide) -->
                            <svg class="eye-slash-icon w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($_SESSION['errors']['confirm_password'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['confirm_password']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/js/password-toggle.js"></script>

<?php
    // Clear errors and old input after displaying
    unset($_SESSION['errors']);
    clear_old_input();
?>
