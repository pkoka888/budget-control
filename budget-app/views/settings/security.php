
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <a href="/settings" class="text-blue-600 hover:underline">← Back to Settings</a>
                    <h1 class="text-2xl font-bold text-gray-800 mt-2">Security Settings</h1>
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
                                <a href="/settings/profile" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors text-gray-700">
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
                                <a href="/settings/security" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors settings-nav-item-active bg-blue-50 text-blue-700">
                                    <span class="text-xl">🔒</span>
                                    <span class="font-medium">Security</span>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Security Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Password Change -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Change Password</h2>
                            <p class="text-gray-600 mt-1">Update your account password regularly for security</p>
                        </div>

                        <form method="POST" action="/settings/security/password" class="p-6 space-y-6">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <input type="password" id="current_password" name="current_password"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter your current password" required>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input type="password" id="new_password" name="new_password"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter new password" required>
                                    <div class="mt-2">
                                        <div class="text-xs text-gray-600 space-y-1">
                                            <div id="length-check" class="flex items-center">
                                                <span class="w-4 h-4 rounded-full border-2 border-gray-300 mr-2"></span>
                                                At least 8 characters
                                            </div>
                                            <div id="uppercase-check" class="flex items-center">
                                                <span class="w-4 h-4 rounded-full border-2 border-gray-300 mr-2"></span>
                                                One uppercase letter
                                            </div>
                                            <div id="lowercase-check" class="flex items-center">
                                                <span class="w-4 h-4 rounded-full border-2 border-gray-300 mr-2"></span>
                                                One lowercase letter
                                            </div>
                                            <div id="number-check" class="flex items-center">
                                                <span class="w-4 h-4 rounded-full border-2 border-gray-300 mr-2"></span>
                                                One number
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Confirm new password" required>
                                    <div id="match-check" class="mt-2 text-xs text-gray-600 flex items-center">
                                        <span class="w-4 h-4 rounded-full border-2 border-gray-300 mr-2"></span>
                                        Passwords match
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Two-Factor Authentication -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Two-Factor Authentication</h2>
                            <p class="text-gray-600 mt-1">Add an extra layer of security to your account</p>
                        </div>

                        <div class="p-6">
                            <?php if (($user['two_factor_enabled'] ?? false)): ?>
                                <!-- 2FA Enabled -->
                                <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                            <span class="text-white text-lg">✓</span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-green-900">Two-Factor Authentication Enabled</div>
                                            <div class="text-sm text-green-700">Your account is protected with 2FA</div>
                                        </div>
                                    </div>
                                    <button onclick="disable2FA()" class="px-4 py-2 border border-green-300 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                                        Disable
                                    </button>
                                </div>

                                <div class="mt-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Backup Codes</h3>
                                    <p class="text-gray-600 text-sm mb-4">Keep these backup codes in a safe place. You can use them to access your account if you lose your device.</p>
                                    <div class="bg-gray-50 p-4 rounded-lg font-mono text-sm">
                                        <?php foreach (($user['backup_codes'] ?? []) as $code): ?>
                                            <div class="inline-block bg-white px-2 py-1 rounded border mr-2 mb-2"><?php echo htmlspecialchars($code); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button onclick="regenerateBackupCodes()" class="mt-3 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm">
                                        Regenerate Codes
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- 2FA Disabled -->
                                <div class="text-center py-8">
                                    <div class="text-6xl mb-4">🔐</div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">Enable Two-Factor Authentication</h3>
                                    <p class="text-gray-600 mb-6">Add an extra layer of security to your account with 2FA. We'll send a code to your device each time you sign in.</p>
                                    <button onclick="enable2FA()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        Enable 2FA
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Active Sessions -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Active Sessions</h2>
                            <p class="text-gray-600 mt-1">Manage your active login sessions</p>
                        </div>

                        <div class="p-6">
                            <div class="space-y-4">
                                <?php foreach (($user['active_sessions'] ?? []) as $session): ?>
                                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm">
                                                <?php echo strtoupper(substr($session['device'] ?? 'D', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($session['device'] ?? 'Unknown Device'); ?>
                                                    <?php if ($session['current'] ?? false): ?>
                                                        <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Current</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($session['location'] ?? 'Unknown Location'); ?> •
                                                    Last active: <?php echo date('M j, Y H:i', strtotime($session['last_active'] ?? 'now')); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (!($session['current'] ?? false)): ?>
                                            <button onclick="revokeSession('<?php echo $session['id']; ?>')" class="px-3 py-1 border border-red-300 text-red-700 rounded text-sm hover:bg-red-50 transition-colors">
                                                Revoke
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <button onclick="revokeAllSessions()" class="px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition-colors">
                                    Revoke All Other Sessions
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Login Notifications -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Login Notifications</h2>
                            <p class="text-gray-600 mt-1">Get notified about account activity</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">🚪</span>
                                    <div>
                                        <div class="font-medium text-gray-900">New Login Alerts</div>
                                        <div class="text-sm text-gray-600">Get notified when someone logs into your account</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="login_alerts" value="1"
                                           <?php echo ($user['login_alerts'] ?? true) ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">📍</span>
                                    <div>
                                        <div class="font-medium text-gray-900">Unusual Location Alerts</div>
                                        <div class="text-sm text-gray-600">Alert when login from unusual location</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="location_alerts" value="1"
                                           <?php echo ($user['location_alerts'] ?? true) ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="bg-white rounded-lg shadow border-l-4 border-red-500">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-red-800">Danger Zone</h2>
                            <p class="text-red-600 mt-1">Irreversible and destructive actions</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="p-4 border border-red-200 rounded-lg bg-red-50">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-red-900">Delete Account</h3>
                                        <p class="text-red-700 text-sm mt-1">
                                            Permanently delete your account and all associated data. This action cannot be undone.
                                        </p>
                                        <div class="mt-3 text-xs text-red-600">
                                            • All your financial data will be permanently removed<br>
                                            • You will lose access to all your budgets, goals, and transactions<br>
                                            • This action is irreversible
                                        </div>
                                    </div>
                                    <button onclick="deleteAccount()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                                        Delete Account
                                    </button>
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
// Password strength validation
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const checks = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /\d/.test(password)
    };

    // Update checkmarks
    Object.keys(checks).forEach(check => {
        const element = document.getElementById(check + '-check');
        const indicator = element.querySelector('span');
        if (checks[check]) {
            indicator.className = 'w-4 h-4 rounded-full bg-green-500 mr-2';
        } else {
            indicator.className = 'w-4 h-4 rounded-full border-2 border-gray-300 mr-2';
        }
    });
});

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirm = this.value;
    const element = document.getElementById('match-check');
    const indicator = element.querySelector('span');

    if (confirm && password === confirm) {
        indicator.className = 'w-4 h-4 rounded-full bg-green-500 mr-2';
        element.className = 'mt-2 text-xs text-green-600 flex items-center';
    } else {
        indicator.className = 'w-4 h-4 rounded-full border-2 border-gray-300 mr-2';
        element.className = 'mt-2 text-xs text-gray-600 flex items-center';
    }
});

function enable2FA() {
    alert('Enable 2FA functionality coming soon!');
    // TODO: Implement 2FA setup
}

function disable2FA() {
    if (confirm('Are you sure you want to disable two-factor authentication? This will make your account less secure.')) {
        alert('Disable 2FA functionality coming soon!');
        // TODO: Implement 2FA disable
    }
}

function regenerateBackupCodes() {
    if (confirm('This will invalidate your current backup codes. Are you sure?')) {
        alert('Regenerate backup codes functionality coming soon!');
        // TODO: Implement backup code regeneration
    }
}

function revokeSession(sessionId) {
    if (confirm('Are you sure you want to revoke this session?')) {
        alert('Revoke session functionality coming soon!');
        // TODO: Implement session revocation
    }
}

function revokeAllSessions() {
    if (confirm('Are you sure you want to revoke all other sessions? You will need to log in again on other devices.')) {
        alert('Revoke all sessions functionality coming soon!');
        // TODO: Implement revoke all sessions
    }
}

function deleteAccount() {
    const confirmation = prompt('This action cannot be undone. Type "DELETE" to confirm:');
    if (confirmation === 'DELETE') {
        if (confirm('Are you absolutely sure? This will permanently delete your account and all data.')) {
            alert('Account deletion functionality coming soon!');
            // TODO: Implement account deletion
        }
    } else {
        alert('Account deletion cancelled.');
    }
}
</script>
