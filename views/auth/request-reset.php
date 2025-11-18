<?php $title = 'Reset Password - Student Skills Marketplace'; ?>

<h2 class="text-2xl font-bold text-gray-900 mb-6">Reset Your Password</h2>

<p class="text-gray-600 mb-6">
    Enter your email address and we'll send you instructions to reset your password.
</p>

<?php if (isset($_SESSION['errors']['general'])): ?>
    <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-md">
        <?php echo e($_SESSION['errors']['general'])?>
    </div>
<?php endif; ?>

<form method="POST" action="/auth/request-reset" class="space-y-6" data-loading>
    <?php echo csrf_field()?>

    <!-- Email -->
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
            Email Address
        </label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?php echo e(old('email'))?>"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo isset($_SESSION['errors']['email']) ? 'border-red-500' : ''?>"
            required
            autofocus
        >
        <?php if (isset($_SESSION['errors']['email'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['email'])?></p>
        <?php endif; ?>
    </div>

    <!-- Submit Button -->
    <button
        type="submit"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium"
    >
        Send Reset Instructions
    </button>
</form>

<div class="mt-6 text-center text-sm text-gray-600">
    Remember your password?
    <a href="/auth/login" class="text-blue-600 hover:text-blue-700 font-medium">Sign in</a>
</div>

<?php
    // Clear errors after displaying
    unset($_SESSION['errors']);
?>
