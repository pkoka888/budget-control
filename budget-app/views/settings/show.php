
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Settings</h1>
                <a href="/" class="text-blue-600 hover:underline">← Back to Dashboard</a>
            </div>

            <?php
            // flash data is available from extract($data)
            if ($flash):
            ?>
                <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Settings Navigation -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6">
                            <h2 class="text-lg font-bold text-gray-800 mb-4">Settings</h2>
                            <nav class="space-y-2">
                                <a href="/settings/profile" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors <?php echo $_SERVER['REQUEST_URI'] === '/settings/profile' ? 'settings-nav-item-active bg-blue-50 text-blue-700' : 'text-gray-700'; ?>">
                                    <span class="text-xl">👤</span>
                                    <span class="font-medium">Profile</span>
                                </a>
                                <a href="/settings/notifications" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors <?php echo $_SERVER['REQUEST_URI'] === '/settings/notifications' ? 'settings-nav-item-active bg-blue-50 text-blue-700' : 'text-gray-700'; ?>">
                                    <span class="text-xl">🔔</span>
                                    <span class="font-medium">Notifications</span>
                                </a>
                                <a href="/settings/preferences" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors <?php echo $_SERVER['REQUEST_URI'] === '/settings/preferences' ? 'settings-nav-item-active bg-blue-50 text-blue-700' : 'text-gray-700'; ?>">
                                    <span class="text-xl">⚙️</span>
                                    <span class="font-medium">Preferences</span>
                                </a>
                                <a href="/settings/automation" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors <?php echo $_SERVER['REQUEST_URI'] === '/settings/automation' ? 'settings-nav-item-active bg-blue-50 text-blue-700' : 'text-gray-700'; ?>">
                                    <span class="text-xl">🤖</span>
                                    <span class="font-medium">Automation</span>
                                </a>
                                <a href="/settings/security" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors <?php echo $_SERVER['REQUEST_URI'] === '/settings/security' ? 'settings-nav-item-active bg-blue-50 text-blue-700' : 'text-gray-700'; ?>">
                                    <span class="text-xl">🔒</span>
                                    <span class="font-medium">Security</span>
                                </a>
                            </nav>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow mt-6">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <button onclick="exportData()" class="w-full text-left px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-xl">📊</span>
                                        <div>
                                            <div class="font-medium text-gray-800">Export Data</div>
                                            <div class="text-sm text-gray-600">Download your data</div>
                                        </div>
                                    </div>
                                </button>
                                <button onclick="importData()" class="w-full text-left px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-xl">📥</span>
                                        <div>
                                            <div class="font-medium text-gray-800">Import Data</div>
                                            <div class="text-sm text-gray-600">Import from file</div>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-2xl font-bold text-gray-800">Account Overview</h2>
                            <p class="text-gray-600 mt-1">Manage your account settings and preferences</p>
                        </div>

                        <div class="p-6">
                            <!-- Account Summary Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></div>
                                    <div class="text-sm text-blue-600">Account Name</div>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600"><?php echo htmlspecialchars($user['email'] ?? 'user@example.com'); ?></div>
                                    <div class="text-sm text-green-600">Email Address</div>
                                </div>
                                <div class="text-center p-4 bg-purple-50 rounded-lg">
                                    <div class="text-2xl font-bold text-purple-600"><?php echo htmlspecialchars($user['currency'] ?? 'CZK'); ?></div>
                                    <div class="text-sm text-purple-600">Default Currency</div>
                                </div>
                            </div>

                            <!-- Settings Overview -->
                            <div class="space-y-6">
                                <div class="border-l-4 border-blue-500 pl-4">
                                    <h3 class="text-lg font-bold text-gray-800">Profile Settings</h3>
                                    <p class="text-gray-600 mb-3">Update your personal information and account details.</p>
                                    <a href="/settings/profile" class="text-blue-600 hover:underline font-medium">Manage Profile →</a>
                                </div>

                                <div class="border-l-4 border-yellow-500 pl-4">
                                    <h3 class="text-lg font-bold text-gray-800">Notification Preferences</h3>
                                    <p class="text-gray-600 mb-3">Control how and when you receive notifications.</p>
                                    <a href="/settings/notifications" class="text-blue-600 hover:underline font-medium">Manage Notifications →</a>
                                </div>

                                <div class="border-l-4 border-green-500 pl-4">
                                    <h3 class="text-lg font-bold text-gray-800">App Preferences</h3>
                                <div class="border-l-4 border-purple-500 pl-4">
                                    <h3 class="text-lg font-bold text-gray-800">Automation & AI</h3>
                                    <p class="text-gray-600 mb-3">Manage automated actions, AI recommendations, and advanced features.</p>
                                    <a href="/settings/automation" class="text-blue-600 hover:underline font-medium">Manage Automation →</a>
                                </div>
                                    <p class="text-gray-600 mb-3">Customize your app experience with language, currency, and display options.</p>
                                    <a href="/settings/preferences" class="text-blue-600 hover:underline font-medium">Manage Preferences →</a>
                                </div>

                                <div class="border-l-4 border-red-500 pl-4">
                                    <h3 class="text-lg font-bold text-gray-800">Security Settings</h3>
                                    <p class="text-gray-600 mb-3">Manage your password, two-factor authentication, and account security.</p>
                                    <a href="/settings/security" class="text-blue-600 hover:underline font-medium">Manage Security →</a>
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
function exportData() {
    // TODO: Implement data export
    alert('Data export functionality coming soon!');
}

function importData() {
    // TODO: Implement data import
    alert('Data import functionality coming soon!');
}
</script>
