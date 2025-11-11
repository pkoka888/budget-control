
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-slate-gray-900">Rozpočty</h1>
                <div class="flex items-center space-x-4">
                    <a href="/budgets/alerts" class="badge badge-warning">
                        🔔 Alerts
                    </a>
                    <input type="month" id="month" value="<?php echo htmlspecialchars($month); ?>" onchange="changeMonth(this.value)" class="form-input max-w-xs">
                </div>
            </div>

            <div class="space-y-4">
                <?php foreach ($budgets as $budget): ?>
                    <?php
                    $hasAlert = isset($budget['alert_count']) && $budget['alert_count'] > 0;
                    $alertSeverity = $budget['alert_severity'] ?? 'none';
                    $alertCount = $budget['alert_count'] ?? 0;
                    ?>
                    <div class="card <?php echo $hasAlert ? 'border-l-4 border-google-yellow-500' : ''; ?>">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-slate-gray-900"><?php echo htmlspecialchars($budget['category_name']); ?></h3>
                                    <?php if ($hasAlert): ?>
                                        <span class="badge <?php echo $alertSeverity === 'critical' ? 'badge-danger' : ($alertSeverity === 'alert' ? 'badge-warning' : 'badge-secondary'); ?>">
                                            <?php echo $alertCount; ?> alert<?php echo $alertCount > 1 ? 's' : ''; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-slate-gray-600">Limit: <strong><?php echo number_format($budget['limit'], 0, ',', ' '); ?> Kč</strong></p>
                                <?php if ($hasAlert): ?>
                                    <p class="text-sm text-google-yellow-700 mt-1">
                                        ⚠️ <?php echo $alertSeverity === 'critical' ? 'Critical' : ($alertSeverity === 'alert' ? 'Alert' : 'Warning'); ?> - Budget limit exceeded
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-slate-gray-900"><?php echo number_format($budget['spent'], 0, ',', ' '); ?> Kč</p>
                                <p class="text-sm text-slate-gray-600"><?php echo number_format($budget['remaining'], 0, ',', ' '); ?> Kč zbývá</p>
                            </div>
                        </div>

                        <div class="mt-4 bg-slate-gray-200 rounded-full h-2">
                            <div class="<?php echo $budget['percentage'] > 100 ? 'bg-google-red-600' : ($budget['percentage'] > 80 ? 'bg-google-yellow-500' : 'bg-google-green-600'); ?> h-2 rounded-full" style="width: <?php echo min($budget['percentage'], 100); ?>%"></div>
                        </div>

                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-sm text-slate-gray-600">
                                <?php echo number_format($budget['percentage'], 1, ',', ' '); ?>% vykázáno
                            </span>
                            <?php if ($hasAlert): ?>
                                <a href="/budgets/alerts?category=<?php echo $budget['category_id']; ?>" class="text-google-yellow-600 hover:text-google-yellow-700 text-sm font-medium">
                                    View alerts →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($budgets)): ?>
                <div class="text-center py-12">
                    <p class="text-slate-gray-600 mb-4">Zatím nemáte žádné rozpočty na tento měsíc.</p>
                    <button onclick="addBudget()" class="btn btn-primary">
                        Přidat rozpočet
                    </button>
                </div>
            <?php else: ?>
                <div class="mt-6">
                    <button onclick="addBudget()" class="btn btn-primary">
                        Přidat další rozpočet
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeMonth(value) {
    window.location.href = '/budgets?month=' + value;
}

function addBudget() {
    // TODO: Implement budget modal
    alert('Add budget modal');
}
</script>
