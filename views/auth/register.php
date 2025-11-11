<?php $title = 'Register - Student Skills Marketplace'; ?>

<h2 class="text-2xl font-bold text-gray-900 mb-6">Create Your Account</h2>

<?php if (isset($_SESSION['errors']['general'])): ?>
    <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-md">
        <?php echo e($_SESSION['errors']['general'])?>
    </div>
<?php endif; ?>

<form method="POST" action="/auth/register" class="space-y-6">
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
        >
        <?php if (isset($_SESSION['errors']['email'])): ?>
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['email'])?></p>
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
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo isset($_SESSION['errors']['password']) ? 'border-red-500' : ''?>"
            required
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
            Confirm Password
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

    <!-- Role Selection -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            I want to
        </label>
        <div class="space-y-2">
            <label class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50 <?php echo old('role') === 'student' ? 'bg-blue-50 border-blue-500' : ''?>">
                <input
                    type="radio"
                    name="role"
                    value="student"
                    <?php echo old('role') === 'student' ? 'checked' : ''?>
                    class="mr-3"
                    required
                >
                <div>
                    <div class="font-medium text-gray-900">Offer my skills</div>
                    <div class="text-sm text-gray-600">I'm a student looking to provide services</div>
                </div>
            </label>
            <label class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50 <?php echo old('role') === 'client' ? 'bg-blue-50 border-blue-500' : ''?>">
                <input
                    type="radio"
                    name="role"
                    value="client"
                    <?php echo old('role') === 'client' ? 'checked' : ''?>
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
            <p class="mt-1 text-sm text-red-600"><?php echo e($_SESSION['errors']['role'])?></p>
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

<?php
    // Clear errors after displaying
    unset($_SESSION['errors']);
?>
