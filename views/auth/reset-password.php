<?php $title = 'Reset Password - Student Skills Marketplace'; ?>

<h2 class="text-2xl font-bold text-gray-900 mb-6">Create New Password</h2>

<p class="text-gray-600 mb-6">
    Enter your new password below.
</p>

<?php if (isset($_SESSION['errors']['general'])): ?>
    <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-md">
        <?php echo e($_SESSION['errors']['general'])?>
    </div>
<?php endif; ?>

<form method="POST" action="/auth/reset-password" class="space-y-6">
    <?php echo csrf_field()?>
    <input type="hidden" name="token" value="<?php echo e($token)?>">

    <!-- Password -->
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
            New Password
        </label>
        <input
            type="password"
            id="password"
            name="password"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo isset($_SESSION['errors']['password']) ? 'border-red-500' : ''?>"
            required
            autofocus
        >
        <?php if (isset($_SESSION['errors']['password'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['password'])?></p>
        <?php else: ?>
            <p class="mt-1 text-sm text-gray-500">Must be at least 8 characters</p>
        <?php endif; ?>
    </div>

    <!-- Password Confirmation -->
    <div>
        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">
            Confirm New Password
        </label>
        <input
            type="password"
            id="password_confirm"
            name="password_confirm"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo isset($_SESSION['errors']['password_confirm']) ? 'border-red-500' : ''?>"
            required
        >
        <?php if (isset($_SESSION['errors']['password_confirm'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['password_confirm'])?></p>
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

<?php
    // Clear errors after displaying
    unset($_SESSION['errors']);
?>
