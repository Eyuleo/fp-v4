<?php
    /**
     * Client Profile Edit View
     */
?>

<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Edit Profile</h1>
            <p class="text-gray-600 mt-2">Update your profile information</p>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['alert'])): ?>
            <div class="mb-6 px-4 py-3 rounded<?php echo $_SESSION['alert']['type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700' ?>">
                <?php echo e($_SESSION['alert']['message']) ?>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <!-- General Error Message -->
        <?php if (isset($_SESSION['errors']['general'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo e($_SESSION['errors']['general']) ?>
            </div>
        <?php endif; ?>

        <form action="/client/profile/update" method="POST" enctype="multipart/form-data" class="space-y-6" id="profile-form">
            <?php echo csrf_field() ?>

            <!-- Profile Picture Section -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Profile Picture
                </label>
                <div class="flex items-center space-x-6">
                    <div class="shrink-0">
                        <?php if (! empty($user['profile_picture'])): ?>
                            <img
                                id="profile-picture-preview"
                                src="/storage/file?path=client-profiles/<?php echo e($user['id']) ?>/<?php echo e($user['profile_picture']) ?>"
                                alt="Profile picture"
                                class="h-24 w-24 object-cover rounded-full border-2 border-gray-300"
                            >
                        <?php else: ?>
                            <div id="profile-picture-preview" class="h-24 w-24 rounded-full bg-blue-600 flex items-center justify-center text-white text-3xl font-bold border-2 border-gray-300">
                                <?php echo strtoupper(substr($user['name'] ?? $user['email'] ?? 'C', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            id="profile_picture"
                            name="profile_picture"
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            onchange="previewProfilePicture(event)"
                        >
                        <p class="text-gray-500 text-sm mt-1">JPEG, PNG, GIF, or WebP. Max 5MB.</p>
                        <?php if (isset($_SESSION['errors']['profile_picture'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['profile_picture']) ?></p>
                        <?php endif; ?>
                        <?php if (! empty($user['profile_picture'])): ?>
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

            <!-- Name Section -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Name
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500                                                                                                                                  <?php echo isset($_SESSION['errors']['name']) ? 'border-red-500' : '' ?>"
                    placeholder="Enter your name"
                    value="<?php echo e(old('name', $user['name'] ?? '')) ?>"
                >
                <?php if (isset($_SESSION['errors']['name'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo e($_SESSION['errors']['name']) ?></p>
                <?php endif; ?>
                <p class="text-gray-500 text-sm mt-1">Between 2 and 255 characters</p>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="/client/dashboard" class="text-gray-600 hover:text-gray-800">
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
// Client-side validation for file size and type
document.getElementById('profile-form').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('profile_picture');
    const file = fileInput.files[0];

    if (file) {
        // Check file size (5MB = 5 * 1024 * 1024 bytes)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            e.preventDefault();
            alert('Profile picture must not exceed 5MB');
            return false;
        }

        // Check file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            e.preventDefault();
            alert('Profile picture must be an image file (JPEG, PNG, GIF, or WebP)');
            return false;
        }
    }
});

// Preview profile picture before upload
function previewProfilePicture(event) {
    const file = event.target.files[0];
    if (file) {
        // Validate file size
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Profile picture must not exceed 5MB');
            event.target.value = '';
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Profile picture must be an image file (JPEG, PNG, GIF, or WebP)');
            event.target.value = '';
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profile-picture-preview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Profile picture" class="h-24 w-24 object-cover rounded-full border-2 border-gray-300">';
        };
        reader.readAsDataURL(file);
    }
}

// Delete profile picture via AJAX
function deleteProfilePicture() {
    if (!confirm('Are you sure you want to remove your profile picture?')) {
        return;
    }

    fetch('/client/profile/delete-picture', {
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
            alert('Error: ' + (data.message || 'Failed to delete profile picture'));
        }
    })
    .catch(error => {
        alert('Error deleting profile picture. Please try again.');
        console.error(error);
    });
}
</script>

<?php
    // Clear errors and old input after displaying
    unset($_SESSION['errors']);
    clear_old_input();
?>
