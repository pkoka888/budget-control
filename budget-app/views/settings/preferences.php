
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <a href="/settings" class="text-blue-600 hover:underline">← Back to Settings</a>
                    <h1 class="text-2xl font-bold text-gray-800 mt-2">Preferences</h1>
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
                                <a href="/settings/preferences" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors settings-nav-item-active bg-blue-50 text-blue-700">
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

                <!-- Preferences Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Regional Settings -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Regional Settings</h2>
                            <p class="text-gray-600 mt-1">Configure your regional preferences</p>
                        </div>

                        <form method="POST" action="/settings/preferences" class="p-6 space-y-6">
                            <!-- Currency -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                    <select id="currency" name="currency" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="CZK" <?php echo ($user['currency'] ?? 'CZK') === 'CZK' ? 'selected' : ''; ?>>CZK - Czech Koruna (Kč)</option>
                                        <option value="EUR" <?php echo ($user['currency'] ?? 'CZK') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro (€)</option>
                                        <option value="USD" <?php echo ($user['currency'] ?? 'CZK') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar ($)</option>
                                        <option value="GBP" <?php echo ($user['currency'] ?? 'CZK') === 'GBP' ? 'selected' : ''; ?>>GBP - British Pound (£)</option>
                                        <option value="PLN" <?php echo ($user['currency'] ?? 'CZK') === 'PLN' ? 'selected' : ''; ?>>PLN - Polish Złoty (zł)</option>
                                    </select>
                                    <p class="text-gray-500 text-sm mt-1">Your primary currency for all financial data</p>
                                </div>

                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                                    <select id="timezone" name="timezone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="Europe/Prague" <?php echo ($user['timezone'] ?? 'Europe/Prague') === 'Europe/Prague' ? 'selected' : ''; ?>>Europe/Prague (CET/CEST)</option>
                                        <option value="Europe/Vienna" <?php echo ($user['timezone'] ?? 'Europe/Prague') === 'Europe/Vienna' ? 'selected' : ''; ?>>Europe/Vienna (CET/CEST)</option>
                                        <option value="Europe/London" <?php echo ($user['timezone'] ?? 'Europe/Prague') === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT/BST)</option>
                                        <option value="Europe/Berlin" <?php echo ($user['timezone'] ?? 'Europe/Prague') === 'Europe/Berlin' ? 'selected' : ''; ?>>Europe/Berlin (CET/CEST)</option>
                                        <option value="America/New_York" <?php echo ($user['timezone'] ?? 'Europe/Prague') === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST/EDT)</option>
                                        <option value="Asia/Tokyo" <?php echo ($user['timezone'] ?? 'Europe/Prague') === 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo (JST)</option>
                                        <option value="UTC" <?php echo ($user['timezone'] ?? 'Europe/Prague') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    </select>
                                    <p class="text-gray-500 text-sm mt-1">Used for date and time display</p>
                                </div>
                            </div>

                            <!-- Date & Number Format -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="date_format" class="block text-sm font-medium text-gray-700 mb-2">Date Format</label>
                                    <select id="date_format" name="date_format" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="d.m.Y" <?php echo ($user['date_format'] ?? 'd.m.Y') === 'd.m.Y' ? 'selected' : ''; ?>>31.12.2023 (European)</option>
                                        <option value="m/d/Y" <?php echo ($user['date_format'] ?? 'd.m.Y') === 'm/d/Y' ? 'selected' : ''; ?>>12/31/2023 (US)</option>
                                        <option value="Y-m-d" <?php echo ($user['date_format'] ?? 'd.m.Y') === 'Y-m-d' ? 'selected' : ''; ?>>2023-12-31 (ISO)</option>
                                        <option value="d/m/Y" <?php echo ($user['date_format'] ?? 'd.m.Y') === 'd/m/Y' ? 'selected' : ''; ?>>31/12/2023 (UK)</option>
                                    </select>
                                    <p class="text-gray-500 text-sm mt-1">How dates are displayed throughout the app</p>
                                </div>

                                <div>
                                    <label for="number_format" class="block text-sm font-medium text-gray-700 mb-2">Number Format</label>
                                    <select id="number_format" name="number_format" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="european" <?php echo ($user['number_format'] ?? 'european') === 'european' ? 'selected' : ''; ?>>1 234 567,89 (European)</option>
                                        <option value="us" <?php echo ($user['number_format'] ?? 'european') === 'us' ? 'selected' : ''; ?>>1,234,567.89 (US)</option>
                                        <option value="indian" <?php echo ($user['number_format'] ?? 'european') === 'indian' ? 'selected' : ''; ?>>12,34,567.89 (Indian)</option>
                                    </select>
                                    <p class="text-gray-500 text-sm mt-1">How numbers and amounts are formatted</p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Display Settings -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Display Settings</h2>
                            <p class="text-gray-600 mt-1">Customize your app appearance and behavior</p>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Theme -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                                    <span class="text-2xl mr-3">🎨</span>
                                    Theme
                                </h3>
                                <p class="text-gray-600 text-sm mb-4">Choose your preferred color scheme</p>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <label class="flex items-center p-4 border-2 <?php echo ($user['theme'] ?? 'light') === 'light' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="radio" name="theme" value="light"
                                               <?php echo ($user['theme'] ?? 'light') === 'light' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-900">Light</div>
                                            <div class="text-sm text-gray-600">Clean and bright</div>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-4 border-2 <?php echo ($user['theme'] ?? 'light') === 'dark' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="radio" name="theme" value="dark"
                                               <?php echo ($user['theme'] ?? 'light') === 'dark' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-900">Dark</div>
                                            <div class="text-sm text-gray-600">Easy on the eyes</div>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-4 border-2 <?php echo ($user['theme'] ?? 'light') === 'auto' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="radio" name="theme" value="auto"
                                               <?php echo ($user['theme'] ?? 'light') === 'auto' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-900">Auto</div>
                                            <div class="text-sm text-gray-600">Follow system</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Language -->
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                                <select id="language" name="language" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="cs" <?php echo ($user['language'] ?? 'cs') === 'cs' ? 'selected' : ''; ?>>Čeština (Czech)</option>
                                    <option value="en" <?php echo ($user['language'] ?? 'cs') === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="sk" <?php echo ($user['language'] ?? 'cs') === 'sk' ? 'selected' : ''; ?>>Slovenčina (Slovak)</option>
                                    <option value="de" <?php echo ($user['language'] ?? 'cs') === 'de' ? 'selected' : ''; ?>>Deutsch (German)</option>
                                    <option value="pl" <?php echo ($user['language'] ?? 'cs') === 'pl' ? 'selected' : ''; ?>>Polski (Polish)</option>
                                </select>
                                <p class="text-gray-500 text-sm mt-1">Interface language for the application</p>
                            </div>

                            <!-- Items Per Page -->
                            <div>
                                <label for="items_per_page" class="block text-sm font-medium text-gray-700 mb-2">Items Per Page</label>
                                <select id="items_per_page" name="items_per_page" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="10" <?php echo ($user['items_per_page'] ?? 25) == 10 ? 'selected' : ''; ?>>10 items</option>
                                    <option value="25" <?php echo ($user['items_per_page'] ?? 25) == 25 ? 'selected' : ''; ?>>25 items</option>
                                    <option value="50" <?php echo ($user['items_per_page'] ?? 25) == 50 ? 'selected' : ''; ?>>50 items</option>
                                    <option value="100" <?php echo ($user['items_per_page'] ?? 25) == 100 ? 'selected' : ''; ?>>100 items</option>
                                </select>
                                <p class="text-gray-500 text-sm mt-1">Number of items to show per page in lists</p>
                            </div>

                            <!-- Compact Mode -->
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div>
                                        <div class="font-medium text-gray-900">Compact Mode</div>
                                        <div class="text-sm text-gray-600">Show more information in less space</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="compact_mode" value="1"
                                           <?php echo ($user['compact_mode'] ?? false) ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Settings -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Privacy Settings</h2>
                            <p class="text-gray-600 mt-1">Control your data and privacy preferences</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">📊</span>
                                    <div>
                                        <div class="font-medium text-gray-900">Analytics</div>
                                        <div class="text-sm text-gray-600">Help improve the app with usage data</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="analytics_enabled" value="1"
                                           <?php echo ($user['analytics_enabled'] ?? true) ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">🎯</span>
                                    <div>
                                        <div class="font-medium text-gray-900">Personalized Tips</div>
                                        <div class="text-sm text-gray-600">Receive personalized financial tips</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="personalized_tips" value="1"
                                           <?php echo ($user['personalized_tips'] ?? true) ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="resetToDefaults()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Reset to Defaults
                        </button>
                        <button type="submit" form="preferencesForm" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Save Preferences
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resetToDefaults() {
    if (confirm('Are you sure you want to reset all preferences to defaults?')) {
        // Reset form to default values
        document.getElementById('currency').value = 'CZK';
        document.getElementById('timezone').value = 'Europe/Prague';
        document.getElementById('date_format').value = 'd.m.Y';
        document.getElementById('number_format').value = 'european';
        document.getElementById('language').value = 'cs';
        document.getElementById('theme').value = 'light';
        document.getElementById('items_per_page').value = '25';

        // Reset checkboxes
        document.querySelector('input[name="compact_mode"]').checked = false;
        document.querySelector('input[name="analytics_enabled"]').checked = true;
        document.querySelector('input[name="personalized_tips"]').checked = true;
    }
}

// Add form submission handling
document.addEventListener('DOMContentLoaded', function() {
    // Create a form element to wrap all the inputs
    const form = document.createElement('form');
    form.id = 'preferencesForm';
    form.method = 'POST';
    form.action = '/settings/preferences';

    // Move all inputs into the form
    const inputs = document.querySelectorAll('input[name], select[name]');
    inputs.forEach(input => {
        form.appendChild(input.cloneNode(true));
    });

    // Replace the original inputs with the form
    document.querySelector('.lg\\:col-span-2').appendChild(form);
});
</script>
