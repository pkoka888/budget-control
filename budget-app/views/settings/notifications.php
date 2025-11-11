
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <a href="/settings" class="text-blue-600 hover:underline">← Back to Settings</a>
                    <h1 class="text-2xl font-bold text-gray-800 mt-2">Notification Settings</h1>
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
                                <a href="/settings/notifications" class="settings-nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors settings-nav-item-active bg-blue-50 text-blue-700">
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

                <!-- Notifications Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Email Notifications -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Email Notifications</h2>
                            <p class="text-gray-600 mt-1">Choose what email notifications you want to receive</p>
                        </div>

                        <form method="POST" action="/settings/notifications" class="p-6 space-y-6">
                            <!-- Budget Alerts -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <span class="text-2xl mr-3">📊</span>
                                    Budget Alerts
                                </h3>
                                <p class="text-gray-600 text-sm mb-4">Get notified when you approach or exceed your budget limits</p>

                                <div class="space-y-3">
                                    <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox" name="budget_alerts_50" value="1"
                                                   <?php echo ($notifications['budget_alerts_50'] ?? true) ? 'checked' : ''; ?>
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <div>
                                                <div class="font-medium text-gray-900">50% Budget Warning</div>
                                                <div class="text-sm text-gray-600">Alert when you reach 50% of your budget</div>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox" name="budget_alerts_75" value="1"
                                                   <?php echo ($notifications['budget_alerts_75'] ?? true) ? 'checked' : ''; ?>
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <div>
                                                <div class="font-medium text-gray-900">75% Budget Alert</div>
                                                <div class="text-sm text-gray-600">Alert when you reach 75% of your budget</div>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox" name="budget_alerts_100" value="1"
                                                   <?php echo ($notifications['budget_alerts_100'] ?? true) ? 'checked' : ''; ?>
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <div>
                                                <div class="font-medium text-gray-900">100% Budget Exceeded</div>
                                                <div class="text-sm text-gray-600">Alert when you exceed your budget limit</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Goal Reminders -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <span class="text-2xl mr-3">🎯</span>
                                    Goal Reminders
                                </h3>
                                <p class="text-gray-600 text-sm mb-4">Stay on track with your financial goals</p>

                                <div class="space-y-3">
                                    <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox" name="goal_reminders_weekly" value="1"
                                                   <?php echo ($notifications['goal_reminders_weekly'] ?? true) ? 'checked' : ''; ?>
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <div>
                                                <div class="font-medium text-gray-900">Weekly Goal Progress</div>
                                                <div class="text-sm text-gray-600">Weekly summary of your goal progress</div>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox" name="goal_deadline_alerts" value="1"
                                                   <?php echo ($notifications['goal_deadline_alerts'] ?? true) ? 'checked' : ''; ?>
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <div>
                                                <div class="font-medium text-gray-900">Goal Deadline Alerts</div>
                                                <div class="text-sm text-gray-600">Reminders as goal deadlines approach</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Reports -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <span class="text-2xl mr-3">📋</span>
                                    Financial Reports
                                </h3>
                                <p class="text-gray-600 text-sm mb-4">Regular summaries of your financial activity</p>

                                <div class="space-y-3">
                                    <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox" name="weekly_reports" value="1"
                                                   <?php echo ($notifications['weekly_reports'] ?? false) ? 'checked' : ''; ?>
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <div>
                                                <div class="font-medium text-gray-900">Weekly Reports</div>
                                                <div class="text-sm text-gray-600">Weekly summary of transactions and budgets</div>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox" name="monthly_reports" value="1"
                                                   <?php echo ($notifications['monthly_reports'] ?? true) ? 'checked' : ''; ?>
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <div>
                                                <div class="font-medium text-gray-900">Monthly Reports</div>
                                                <div class="text-sm text-gray-600">Comprehensive monthly financial overview</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Alert Frequency -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <span class="text-2xl mr-3">⏰</span>
                                    Alert Frequency
                                </h3>
                                <p class="text-gray-600 text-sm mb-4">How often do you want to receive notifications?</p>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <label class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="radio" name="alert_frequency" value="immediate"
                                               <?php echo ($notifications['alert_frequency'] ?? 'immediate') === 'immediate' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-900">Immediate</div>
                                            <div class="text-sm text-gray-600">As soon as it happens</div>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="radio" name="alert_frequency" value="daily"
                                               <?php echo ($notifications['alert_frequency'] ?? 'immediate') === 'daily' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-900">Daily Digest</div>
                                            <div class="text-sm text-gray-600">Once per day</div>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="radio" name="alert_frequency" value="weekly"
                                               <?php echo ($notifications['alert_frequency'] ?? 'immediate') === 'weekly' ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-900">Weekly Summary</div>
                                            <div class="text-sm text-gray-600">Once per week</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                                <button type="button" onclick="resetToDefaults()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    Reset to Defaults
                                </button>
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Notification History -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="border-b p-6">
                            <h2 class="text-xl font-bold text-gray-800">Recent Notifications</h2>
                            <p class="text-gray-600 mt-1">Your recent notification history</p>
                        </div>

                        <div class="p-6">
                            <?php if (!empty($recent_notifications)): ?>
                                <div class="space-y-3">
                                    <?php foreach (array_slice($recent_notifications, 0, 5) as $notification): ?>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                    <div class="text-sm text-gray-600"><?php echo date('M j, Y H:i', strtotime($notification['sent_at'])); ?></div>
                                                </div>
                                            </div>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Sent</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8 text-gray-500">
                                    <div class="text-4xl mb-2">📭</div>
                                    <p>No notifications sent yet. Your notification history will appear here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resetToDefaults() {
    if (confirm('Are you sure you want to reset all notification preferences to defaults?')) {
        // Check budget alerts by default
        document.querySelectorAll('input[name^="budget_alerts_"]').forEach(cb => cb.checked = true);
        // Check goal deadline alerts by default
        document.querySelector('input[name="goal_deadline_alerts"]').checked = true;
        // Check monthly reports by default
        document.querySelector('input[name="monthly_reports"]').checked = true;
        // Set frequency to immediate
        document.querySelector('input[name="alert_frequency"][value="immediate"]').checked = true;

        // Uncheck others
        document.querySelector('input[name="goal_reminders_weekly"]').checked = false;
        document.querySelector('input[name="weekly_reports"]').checked = false;
    }
}
</script>
