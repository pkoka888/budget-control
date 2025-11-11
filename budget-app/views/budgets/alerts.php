
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Budget Alerts</h1>
                <a href="/budgets" class="text-blue-600 hover:underline">← Back to Budgets</a>
            </div>

            <!-- Filters -->
            <div class="alert-filters">
                <form method="GET" action="/budgets/alerts" class="alert-filters-grid">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="acknowledged" <?php echo (isset($_GET['status']) && $_GET['status'] === 'acknowledged') ? 'selected' : ''; ?>>Acknowledged</option>
                            <option value="dismissed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'dismissed') ? 'selected' : ''; ?>>Dismissed</option>
                        </select>
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" id="start_date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" id="end_date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Filter Alerts
                        </button>
                    </div>

                    <div class="flex items-end">
                        <button type="button" onclick="clearFilters()" class="w-full bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                            Clear Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Alert Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $stats['total_alerts'] ?? 0; ?></div>
                    <div class="text-sm text-gray-600">Total Alerts</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-orange-600"><?php echo $stats['active_alerts'] ?? 0; ?></div>
                    <div class="text-sm text-gray-600">Active</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $stats['acknowledged_alerts'] ?? 0; ?></div>
                    <div class="text-sm text-gray-600">Acknowledged</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-gray-600"><?php echo $stats['dismissed_alerts'] ?? 0; ?></div>
                    <div class="text-sm text-gray-600">Dismissed</div>
                </div>
            </div>

            <!-- Alerts List -->
            <?php if (!empty($alerts)): ?>
                <div class="space-y-4">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert-item">
                            <div class="alert-header">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="alert-badge alert-severity-<?php echo htmlspecialchars($alert['severity']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($alert['severity'])); ?>
                                        </span>
                                        <h3 class="alert-title"><?php echo htmlspecialchars($alert['category_name']); ?></h3>
                                    </div>
                                    <div class="alert-meta">
                                        Triggered: <?php echo date('d.m.Y H:i', strtotime($alert['triggered_at'])); ?> |
                                        Status: <?php echo ucfirst(htmlspecialchars($alert['status'])); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="alert-message">
                                <?php echo htmlspecialchars($alert['message']); ?>
                            </div>

                            <div class="alert-progress">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium">
                                        <?php echo number_format($alert['current_spent'], 0, ',', ' '); ?> Kč spent
                                    </span>
                                    <span class="text-sm text-gray-600">
                                        of <?php echo number_format($alert['budget_amount'], 0, ',', ' '); ?> Kč budget
                                    </span>
                                </div>
                                <div class="alert-progress-bar">
                                    <div class="alert-progress-fill <?php echo htmlspecialchars($alert['severity']); ?>"
                                         style="width: <?php echo min($alert['percentage'], 100); ?>%"></div>
                                </div>
                                <div class="alert-progress-text">
                                    <?php echo number_format($alert['percentage'], 1, ',', ' '); ?>% used
                                </div>
                            </div>

                            <?php if ($alert['status'] === 'active'): ?>
                            <div class="alert-action-buttons">
                                <button onclick="acknowledgeAlert(<?php echo $alert['id']; ?>)"
                                        class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition-colors">
                                    ✓ Acknowledge
                                </button>
                                <button onclick="dismissAlert(<?php echo $alert['id']; ?>)"
                                        class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 transition-colors">
                                    ✕ Dismiss
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex justify-center space-x-2">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="/budgets/alerts?page=<?php echo $p; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>"
                               class="px-4 py-2 rounded <?php echo $p === $page ? 'bg-blue-600 text-white' : 'bg-gray-300 hover:bg-gray-400'; ?>">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State -->
                <div class="alert-empty-state">
                    <div class="alert-empty-icon">🔔</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No budget alerts found</h3>
                    <p class="text-gray-600 mb-4">
                        <?php if (!empty($_GET)): ?>
                            No alerts match your current filters. Try adjusting your search criteria.
                        <?php else: ?>
                            Great job! All your budgets are within safe limits. Keep up the good work!
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($_GET)): ?>
                        <button onclick="clearFilters()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Clear Filters
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function acknowledgeAlert(alertId) {
    if (confirm('Are you sure you want to acknowledge this alert?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/budgets/alerts/' + alertId + '/acknowledge';
        document.body.appendChild(form);
        form.submit();
    }
}

function dismissAlert(alertId) {
    if (confirm('Are you sure you want to dismiss this alert? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/budgets/alerts/' + alertId + '/dismiss';
        document.body.appendChild(form);
        form.submit();
    }
}

function clearFilters() {
    window.location.href = '/budgets/alerts';
}
</script>
