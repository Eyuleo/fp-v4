<?php $title = 'Register - Student Skills Marketplace'; ?>

<h2 class="text-2xl font-bold text-gray-900 mb-6">Create Your Account</h2>

<?php if (isset($_SESSION['errors']['general'])): ?>
    <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-md">
        <?php echo e($_SESSION['errors']['general']) ?>
    </div>
<?php endif; ?>

<form method="POST" action="/auth/register" class="space-y-6">
    <?php echo csrf_field() ?>

    <!-- Email -->
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
            Email Address
        </label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?php echo e(old('email')) ?>"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                            <?php echo isset($_SESSION['errors']['email']) ? 'border-red-500' : '' ?>"
            required
        >
        <?php if (isset($_SESSION['errors']['email'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['email']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Password -->
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
            Password
        </label>
        <div class="password-input-wrapper relative">
            <input
                type="password"
                id="password"
                name="password"
                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                          <?php echo isset($_SESSION['errors']['password']) ? 'border-red-500' : '' ?>"
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
        <?php if (isset($_SESSION['errors']['password'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['password']) ?></p>
        <?php else: ?>
            <p class="mt-1 text-sm text-gray-500">Must be at least 8 characters</p>
        <?php endif; ?>
    </div>

    <!-- Password Confirmation -->
    <div>
        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">
            Confirm Password
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

    <!-- Role Selection -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            I want to
        </label>
        <div class="space-y-2">
            <label class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50                                                                                                                                                                                                                                                                                                                                                    <?php echo old('role') === 'student' ? 'bg-blue-50 border-blue-500' : '' ?>">
                <input
                    type="radio"
                    name="role"
                    value="student"
                    <?php echo old('role') === 'student' ? 'checked' : '' ?>
                    class="mr-3"
                    required
                >
                <div>
                    <div class="font-medium text-gray-900">Offer my skills</div>
                    <div class="text-sm text-gray-600">I'm a student looking to provide services</div>
                </div>
            </label>
            <label class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50                                                                                                                                                                                                                                                                                                                                                    <?php echo old('role') === 'client' ? 'bg-blue-50 border-blue-500' : '' ?>">
                <input
                    type="radio"
                    name="role"
                    value="client"
                    <?php echo old('role') === 'client' ? 'checked' : '' ?>
                    class="mr-3"
                    required
                >
                <div>
                    <div class="font-medium text-gray-900">Hire students</div>
                    <div class="text-sm text-gray-600">I'm looking to purchase services</div>
                </div>
            </label>
        </div>
        <?php if (isset($_SESSION['errors']['role'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['role']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Submit Button -->
    <button
        type="submit"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium"
    >
        Create Account
    </button>
</form>

<div class="mt-6 text-center text-sm text-gray-600">
    Already have an account?
    <a href="/auth/login" class="text-blue-600 hover:text-blue-700 font-medium">Sign in</a>
</div>

<script src="/js/password-toggle.js"></script>

<?php
    // Clear errors after displaying
    unset($_SESSION['errors']);
?>
