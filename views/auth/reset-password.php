<?php $title = 'Reset Password - Student Skills Marketplace'; ?>

<h2 class="text-2xl font-bold text-gray-900 mb-6">Create New Password</h2>

<p class="text-gray-600 mb-6">
    Enter your new password below.
</p>

<?php if (isset($_SESSION['errors']['general'])): ?>
    <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-md">
        <?php echo e($_SESSION['errors']['general']) ?>
    </div>
<?php endif; ?>

<form method="POST" action="/auth/reset-password" class="space-y-6">
    <?php echo csrf_field() ?>
    <input type="hidden" name="token" value="<?php echo e($token) ?>">

    <!-- Password -->
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
            New Password
        </label>
        <div class="password-input-wrapper relative">
            <input
                type="password"
                id="password"
                name="password"
                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                          <?php echo isset($_SESSION['errors']['password']) ? 'border-red-500' : '' ?>"
                required
                autofocus
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
        <?php if (isset($_SESSION['errors']['password'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['password']) ?></p>
        <?php else: ?>
            <p class="mt-1 text-sm text-gray-500">Must be at least 8 characters</p>
        <?php endif; ?>
    </div>

    <!-- Password Confirmation -->
    <div>
        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">
            Confirm New Password
        </label>
        <div class="password-input-wrapper relative">
            <input
                type="password"
                id="password_confirm"
                name="password_confirm"
                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                       <?php echo isset($_SESSION['errors']['password_confirm']) ? 'border-red-500' : '' ?>"
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
        <?php if (isset($_SESSION['errors']['password_confirm'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['password_confirm']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Submit Button -->
    <button
        type="submit"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium"
    >
        Reset Password
    </button>
</form>

<div class="mt-6 text-center text-sm text-gray-600">
    <a href="/auth/login" class="text-blue-600 hover:text-blue-700 font-medium">Back to Login</a>
</div>

<script src="/js/password-toggle.js"></script>

<?php
    // Clear errors after displaying
    unset($_SESSION['errors']);
?>
