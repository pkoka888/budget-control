
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <a href="/settings" class="text-blue-600 hover:underline">← Back to Settings</a>
                    <h1 class="text-2xl font-bold text-gray-800 mt-2">Profile Settings</h1>
                </div>
            </div>

            <?php
            // flash data is available from extract($data)
            if ($flash):
            ?>
                <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Settings Navigation -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6">
                            <h2 class="text-lg font-bold text-gray-800 mb-4">Settings</h2>
                            <nav class="space-y-2">
                                <a href="/settings/profile" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors settings-nav-item-active bg-blue-50 text-blue-700">
                                    <span class="text-xl">👤</span>
                                    <span class="font-medium">Profile</span>
                                </a>
                                <a href="/settings/notifications" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors text-gray-700">
                                    <span class="text-xl">🔔</span>
                                    <span class="font-medium">Notifications</span>
                                </a>
                                <a href="/settings/preferences" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors text-gray-700">
                                    <span class="text-xl">⚙️</span>
                                    <span class="font-medium">Preferences</span>
                                </a>
                                <a href="/settings/security" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors text-gray-700">
                                    <span class="text-xl">🔒</span>
                                    <span class="font-medium">Security</span>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Profile Information -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Profile Information</h2>
                            <p class="text-gray-600 mt-1">Update your personal information</p>
                        </div>

                        <form method="POST" action="/settings/profile" class="p-6 space-y-6">
                            <!-- Avatar Section -->
                            <div class="flex items-center space-x-6">
                                <div class="flex-shrink-0">
                                    <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                        <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">Profile Picture</h3>
                                    <p class="text-gray-600 text-sm">Upload a new profile picture or choose from our defaults.</p>
                                    <div class="mt-3">
                                        <button type="button" onclick="changeAvatar()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm">
                                            Change Avatar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Information -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter your full name">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter your email address">
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div>
                                <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                                <textarea id="bio" name="bio" rows="4"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Tell us a little about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                <p class="text-gray-500 text-sm mt-1">Brief description for your profile. Max 500 characters.</p>
                            </div>

                            <!-- Account Information (Read-only) -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Account Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Member since:</span>
                                        <span class="ml-2 text-gray-900"><?php echo date('M j, Y', strtotime($user['created_at'] ?? 'now')); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Last updated:</span>
                                        <span class="ml-2 text-gray-900"><?php echo date('M j, Y', strtotime($user['updated_at'] ?? 'now')); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                                <button type="button" onclick="resetForm()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    Reset
                                </button>
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Account Statistics -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Account Statistics</h2>
                            <p class="text-gray-600 mt-1">Your activity overview</p>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600"><?php echo $stats['total_transactions'] ?? 0; ?></div>
                                    <div class="text-sm text-blue-600">Total Transactions</div>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600"><?php echo $stats['active_goals'] ?? 0; ?></div>
                                    <div class="text-sm text-green-600">Active Goals</div>
                                </div>
                                <div class="text-center p-4 bg-purple-50 rounded-lg">
                                    <div class="text-2xl font-bold text-purple-600"><?php echo $stats['budget_categories'] ?? 0; ?></div>
                                    <div class="text-sm text-purple-600">Budget Categories</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changeAvatar() {
    // TODO: Implement avatar change functionality
    alert('Avatar change functionality coming soon!');
}

function resetForm() {
    if (confirm('Are you sure you want to reset all changes?')) {
        document.getElementById('name').value = '<?php echo htmlspecialchars($user['name'] ?? ''); ?>';
        document.getElementById('email').value = '<?php echo htmlspecialchars($user['email'] ?? ''); ?>';
        document.getElementById('bio').value = '<?php echo htmlspecialchars($user['bio'] ?? ''); ?>';
    }
}
</script>
