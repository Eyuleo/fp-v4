<?php $title = 'Email Verification - Student Skills Marketplace'; ?>

<div class="text-center">
    <?php if ($success ?? false): ?>
        <!-- Success State -->
        <div class="mb-6">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-4">Email Verified!</h2>
        <p class="text-gray-600 mb-6">
            Your email has been successfully verified. You can now log in to your account.
        </p>

        <a
            href="/auth/login"
            class="inline-block bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 font-medium"
        >
            Go to Login
        </a>
    <?php else: ?>
        <!-- Error State -->
        <div class="mb-6">
            <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-4">Verification Failed</h2>
        <p class="text-gray-600 mb-6">
            <?php echo e($error ?? 'The verification link is invalid or has expired.')?>
        </p>

        <div class="space-y-3">
            <a
                href="/auth/register"
                class="block bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 font-medium"
            >
                Register Again
            </a>
            <a
                href="/auth/login"
                class="block text-blue-600 hover:text-blue-700 font-medium"
            >
                Back to Login
            </a>
        </div>
    <?php endif; ?>
</div>
