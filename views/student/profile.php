<?php
    /**
     * Student Profile Edit View
     */
?>

<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Edit Profile</h1>
            <p class="text-gray-600 mt-2">Update your profile information to attract more clients</p>
        </div>

        <?php if (isset($_SESSION['errors']['general'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo e($_SESSION['errors']['general']) ?>
            </div>
        <?php endif; ?>

        <form action="/student/profile/update" method="POST" enctype="multipart/form-data" class="space-y-6" data-loading>
            <?php echo csrf_field() ?>

            <!-- Profile Picture Section -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Profile Picture
                </label>
                <div class="flex items-center space-x-6">
                    <div class="shrink-0">
                        <?php if (! empty($profile['profile_picture'])): ?>
                            <img
                                id="profile-picture-preview"
                                src="/storage/file?path=profiles/<?php echo e($profile['user_id']) ?>/<?php echo e($profile['profile_picture']) ?>"
                                alt="Profile picture"
                                class="h-24 w-24 object-cover rounded-full border-2 border-gray-300"
                            >
                        <?php else: ?>
                            <div id="profile-picture-preview" class="h-24 w-24 rounded-full bg-blue-600 flex items-center justify-center text-white text-3xl font-bold border-2 border-gray-300">
                                <?php echo strtoupper(substr($user['name'] ?? $user['email'] ?? 'S', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            id="profile_picture"
                            name="profile_picture"
                            accept="image/jpeg,image/png,image/gif"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            onchange="previewProfilePicture(event)"
                        >
                        <p class="text-gray-500 text-sm mt-1">JPG, PNG or GIF. Max 5MB.</p>
                        <?php if (! empty($profile['profile_picture'])): ?>
                            <button
                                type="button"
                                onclick="deleteProfilePicture()"
                                class="mt-2 text-red-600 hover:text-red-800 text-sm font-medium"
                            >
                                Remove profile picture
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bio Section -->
            <div>
                <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                    Bio
                </label>
                <textarea
                    id="bio"
                    name="bio"
                    rows="6"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     <?php echo isset($_SESSION['errors']['bio']) ? 'border-red-500' : '' ?>"
                    placeholder="Tell clients about yourself, your experience, and what makes you unique..."
                ><?php echo e(old('bio', $profile['bio'] ?? '')) ?></textarea>
                <?php if (isset($_SESSION['errors']['bio'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['bio']) ?></p>
                <?php endif; ?>
                <p class="text-gray-500 text-sm mt-1">Maximum 2000 characters</p>
            </div>

            <!-- Skills Section -->
            <div>
                <label for="skills" class="block text-sm font-medium text-gray-700 mb-2">
                    Skills
                </label>
                <input
                    type="text"
                    id="skills"
                    name="skills"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     <?php echo isset($_SESSION['errors']['skills']) ? 'border-red-500' : '' ?>"
                    placeholder="e.g., Web Development, Graphic Design, Content Writing"
                    value="<?php echo e(old('skills', is_array($profile['skills'] ?? null) ? implode(', ', $profile['skills']) : '')) ?>"
                >
                <?php if (isset($_SESSION['errors']['skills'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['skills']) ?></p>
                <?php endif; ?>
                <p class="text-gray-500 text-sm mt-1">Separate skills with commas</p>
            </div>

            <!-- Current Skills Display -->
            <?php if (! empty($profile['skills']) && is_array($profile['skills'])): ?>
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Current Skills:</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($profile['skills'] as $skill): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <?php echo e($skill) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Portfolio Files Section -->
            <div>
                <label for="portfolio_files" class="block text-sm font-medium text-gray-700 mb-2">
                    Portfolio Files
                </label>
                <input
                    type="file"
                    id="portfolio_files"
                    name="portfolio_files[]"
                    multiple
                    accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     <?php echo isset($_SESSION['errors']['portfolio_files']) ? 'border-red-500' : '' ?>"
                >
                <?php if (isset($_SESSION['errors']['portfolio_files'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['portfolio_files']) ?></p>
                <?php endif; ?>
                <p class="text-gray-500 text-sm mt-1">
                    Upload samples of your work. Max 10MB per file. Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX, ZIP
                </p>
            </div>

            <!-- Current Portfolio Files -->
            <?php if (! empty($profile['portfolio_files']) && is_array($profile['portfolio_files'])): ?>
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Current Portfolio Files:</p>
                    <div class="space-y-2">
                        <?php foreach ($profile['portfolio_files'] as $file): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                <div class="flex items-center space-x-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo e($file['original_name'] ?? 'Unknown') ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo e(number_format(($file['size'] ?? 0) / 1024, 2)) ?> KB
                                            <?php if (isset($file['uploaded_at'])): ?>
                                                • Uploaded<?php echo e(date('M d, Y', strtotime($file['uploaded_at']))) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    onclick="deletePortfolioFile('<?php echo e($file['filename'] ?? '') ?>')"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                                >
                                    Delete
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stripe Connect Section (Placeholder for task 5.2) -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Payment Setup</h3>
                <p class="text-gray-600 mb-4">Connect your Stripe account to receive payments</p>

                <?php if (! empty($profile['stripe_onboarding_complete'])): ?>
                    <div class="flex items-center space-x-2 text-green-600">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium">Stripe account connected</span>
                    </div>
                <?php else: ?>
                    <a
                        href="/student/profile/connect-stripe"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                        Connect Stripe Account
                    </a>
                <?php endif; ?>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="/student/dashboard" class="text-gray-600 hover:text-gray-800">
                    Cancel
                </a>
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Save Profile
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewProfilePicture(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profile-picture-preview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Profile picture" class="h-24 w-24 object-cover rounded-full border-2 border-gray-300">';
        };
        reader.readAsDataURL(file);
    }
}

function deleteProfilePicture() {
    if (!confirm('Are you sure you want to remove your profile picture?')) {
        return;
    }

    fetch('/student/profile/delete-picture', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'csrf_token=<?php echo e(csrf_token()) ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error deleting profile picture');
        console.error(error);
    });
}

function deletePortfolioFile(filename) {
    if (!confirm('Are you sure you want to delete this file?')) {
        return;
    }

    fetch('/student/profile/delete-file', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'csrf_token=<?php echo e(csrf_token()) ?>&filename=' + encodeURIComponent(filename)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error deleting file');
        console.error(error);
    });
}
</script>

<?php
    // Clear errors and old input after displaying
    unset($_SESSION['errors']);
    clear_old_input();
?>
