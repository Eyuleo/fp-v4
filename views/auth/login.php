<?php $title = 'Login - Student Skills Marketplace'; ?>

<h2 class="text-2xl font-bold text-gray-900 mb-6">Sign In</h2>

<?php if (isset($stripe_return) && $stripe_return): ?>
    <div class="mb-4 p-4 bg-blue-50 text-blue-800 rounded-md">
        <p class="font-medium">Stripe setup complete!</p>
        <p class="text-sm mt-1">Please log in to continue and view your updated profile.</p>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['errors']['general'])): ?>
    <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-md">
        <?php echo e($_SESSION['errors']['general']) ?>
    </div>
<?php endif; ?>

<form method="POST" action="/auth/login" class="space-y-6">
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
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                          <?php echo isset($_SESSION['errors']['email']) ? 'border-red-500' : '' ?>"
            required
            autofocus
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
        <input
            type="password"
            id="password"
            name="password"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                          <?php echo isset($_SESSION['errors']['password']) ? 'border-red-500' : '' ?>"
            required
        >
        <?php if (isset($_SESSION['errors']['password'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['password']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Forgot Password Link -->
    <div class="text-right">
        <a href="/auth/request-reset" class="text-sm text-blue-600 hover:text-blue-700">
            Forgot your password?
        </a>
    </div>

    <!-- Submit Button -->
    <button
        type="submit"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium"
    >
        Sign In
    </button>
</form>

<div class="mt-6 text-center text-sm text-gray-600">
    Don't have an account?
    <a href="/auth/register" class="text-blue-600 hover:text-blue-700 font-medium">Create one</a>
</div>

<?php
    // Clear errors after displaying
    unset($_SESSION['errors']);
?>
